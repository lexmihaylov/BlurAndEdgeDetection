<?php
include './ImageManipulator.php';
$params = $_GET;
$manipulator = new ImageManipulator($params['image']);
if(isset($params['grayscale'])) $manipulator->grayscale(ImageManipulator::$LUMINOSITY);

if(isset($params['gaussian_blur'])) $manipulator->gaussian_filter();

if(isset($params['sobel'])) $manipulator->sobel_detect_edges();

if(isset($params['experimental'])) $manipulator->edge_test(30);

$manipulator->showImage();

$manipulator->close();
?>