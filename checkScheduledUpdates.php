<?php
	require_once(__DIR__ . '/functions.php');

	if (!$enableScheduledUpdates) { die('Scheduled updates not enabled for this instance.'."\n"); }

	echo date('r'), ' - Started', "\n";

	use PhpAmqpLib\Connection\AMQPStreamConnection;
	use PhpAmqpLib\Message\AMQPMessage;
	use PhpAmqpLib\Exception\AMQPTimeoutException;

	$connection = new AMQPStreamConnection($rabbitmq['server'], $rabbitmq['port'], $rabbitmq['username'], $rabbitmq['password'], $rabbitmq['vhost']);
	$channel = $connection->channel();

	$channel->exchange_declare('events', 'topic', false, false, false);
	[$myQueue] = $channel->queue_declare('', false, false, true, false);
	$channel->queue_bind($myQueue, 'events', '#');
	$channel->basic_consume($myQueue, '', false, true, false, false, function ($msg) {
		$m = json_decode($msg->body, true);
		echo $msg->body, "\n";
		handleScheduledUpdate($m['instance']);
	});

	while ($channel->is_consuming() && $channel->is_open()) {
		try {
			$channel->wait(null, false, 60);
		} catch (AMQPTimeoutException $e) { }
	}

	echo date('r'), ' - Exited', "\n";
