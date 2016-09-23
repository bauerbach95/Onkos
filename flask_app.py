from flask import Flask
from flask import render_template, abort, jsonify, request, redirect, json
app = Flask(__name__)
app.debug = True
import pickle
import os
import scipy
from PIL import Image
import cv2
import numpy as np
import sklearn
import skflow
from sklearn.feature_extraction import sklearn_image
from skflow import TensorFlowEstimator
from tensorflow.contrib.learn import TensorFlowEstimator
import tensorflow as tf

def model(X, w, w2, w3, w4, w_o, p_keep_conv, p_keep_hidden):
    l1a = tf.nn.relu(tf.nn.conv2d(X, w,                       # l1a shape=(?, 28, 28, 32)
                        strides=[1, 1, 1, 1], padding='SAME'))
    l1 = tf.nn.max_pool(l1a, ksize=[1, 2, 2, 1],              # l1 shape=(?, 14, 14, 32)
                        strides=[1, 2, 2, 1], padding='SAME')
    l1 = tf.nn.dropout(l1, p_keep_conv)

    l2a = tf.nn.relu(tf.nn.conv2d(l1, w2,                     # l2a shape=(?, 14, 14, 64)
                        strides=[1, 1, 1, 1], padding='SAME'))
    l2 = tf.nn.max_pool(l2a, ksize=[1, 2, 2, 1],              # l2 shape=(?, 7, 7, 64)
                        strides=[1, 2, 2, 1], padding='SAME')
    l2 = tf.nn.dropout(l2, p_keep_conv)

    l3a = tf.nn.relu(tf.nn.conv2d(l2, w3,                     # l3a shape=(?, 7, 7, 128)
                        strides=[1, 1, 1, 1], padding='SAME'))
    l3 = tf.nn.max_pool(l3a, ksize=[1, 2, 2, 1],              # l3 shape=(?, 4, 4, 128)
                        strides=[1, 2, 2, 1], padding='SAME')
    l3 = tf.reshape(l3, [-1, w4.get_shape().as_list()[0]])    # reshape to (?, 2048)
    l3 = tf.nn.dropout(l3, p_keep_conv)

    l4 = tf.nn.relu(tf.matmul(l3, w4))
    l4 = tf.nn.dropout(l4, p_keep_hidden)

    pyx = tf.matmul(l4, w_o)
    return pyx


@app.route('/')
def index():
	return render_template('main.html')

def init_weights(shape):
    return tf.Variable(tf.random_normal(shape, stddev=0.01))

def preprocess_data(data):
	a = scipy.misc.imread(data)

	b = cv2.resize(a, (350,230), interpolation=cv2.INTER_AREA)
	b = np.array(b)
	patches = sklearn_image.extract_patches_2d(b, (32, 32) , max_patches = 1000)
	pickle.dump(patches, open("malignant.pkl" , "wb"))

	return patches

@app.route('/get_prediction', methods=['POST'])
def learning():
	f = request.files['file']

	# preprocess data
	patches = preprocess_data(f)

	# load patches
	patches = pickle.loads("malignant.pkl")

	# set test size
	test_size = 603

	# load clf and predict
	X = tf.placeholder("float", [None, 32, 32, 3])
	Y = tf.placeholder("float", [None, 2])
	Y1 = tf.placeholder("float", [test_size,])
	w = init_weights([3, 3, 3, 32])       # 3x3x1 conv, 32 outputs
	w2 = init_weights([3, 3, 32, 64])     # 3x3x32 conv, 64 outputs
	w3 = init_weights([3, 3, 64, 128])    # 3x3x32 conv, 128 outputs
	w4 = init_weights([128 * 4 * 4, 625]) # FC 128 * 4 * 4 inputs, 625 outputs
	w_o = init_weights([625, 2])         # FC 625 inputs, 10 outputs (labels)

	p_keep_conv = tf.placeholder("float")
	p_keep_hidden = tf.placeholder("float")
	py_x = model(X, w, w2, w3, w4, w_o, p_keep_conv, p_keep_hidden)

	cost = tf.reduce_mean(tf.nn.softmax_cross_entropy_with_logits(py_x, Y))
	train_op = tf.train.RMSPropOptimizer(0.001, 0.9).minimize(cost)
	predict_op = tf.argmax(py_x, 1)

	saver = tf.train.Saver() # saver object

	with tf.Session() as sess:
		saver.restore(sess, "/tmp/model.ckpt")
		predictions = np.array(sess.run(predict_op, feed_dict={X: patches, p_keep_conv: 1.0, p_keep_hidden: 1.0}))
		num_predictions = len(predictions)
		num_of_ones = np.array(predictions = 1)
		num_of_zeros = np.array(predictions = 0)

		prediction = "Malignant" if num_of_ones > num_of_zeros else "Benign"


	response = {"prediction" : prediction}
	return jsonify(response)


if __name__ == '__main__':
	app.run(host= '0.0.0.0')