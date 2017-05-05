<?php

/**
 * The Nextcloud server control is used by the acceptance tests to remotely
 * control the Nextcloud server.
 *
 * The acceptance tests need to perform certain operations in the Nextcloud
 * server that require access to the system it is running on, like resetting the
 * server to a default state. The Nextcloud server control is used in those
 * cases in which the acceptance tests do not have access to the system, like
 * when running in Drone.
 *
 * In order to use the Nextcloud server control the system must be set up in a
 * specific way: the Nextcloud server must be installed in "/var/www/html",
 * using local data storage, and a snapshot of the whole "/var/www/html"
 * directory (no ".gitignore" file is used) in its default state must be stored
 * in a Git repository, also in "/var/www/html". Moreover, the same user that
 * runs Nextcloud server control must be able to reset the Git repository and
 * execute "/usr/sbin/apache2ctl" (thus, if Nextcloud server control is run as
 * a user like "www-data", Apache must be configured to listen on a
 * non-privileged port).
 */

namespace NextcloudServerControl {

class SocketException extends \Exception {
	public function __construct($message) {
		parent::__construct($message);
	}
}

/**
 * Common class for communication between client and server.
 *
 * Clients and server communicate through messages: a client sends a request and
 * the server answers with a response. Requests and responses all have the same
 * common structure composed by a mandatory header and optional data. The header
 * contains a code that identifies the type of request or response followed by
 * the length of the data (which can be 0). The data is a free form string that
 * depends on each request and response type.
 *
 * The Messenger abstracts all that and provides two public methods: readMessage
 * and writeMessage. For each connection a client first writes the request
 * message and then reads the response message, while the server first reads the
 * request message and then writes the response message. If the client needs to
 * send another request it must connect again to the server.
 *
 * The Messenger class in the server must be kept in sync with the Messenger
 * class in the client. Due to the size of the code and its current use it was
 * more practical, at least for the time being, to keep two copies of the code
 * than creating a library that had to be downloaded and included in the client
 * and in the server.
 */
class Messenger {

	/**
	 * Reset the Nextcloud server.
	 *
	 * -Request data: empty
	 * -OK response data: empty.
	 * -Failed response data: error information.
	 */
	const CODE_REQUEST_RESET = 0;

	const CODE_RESPONSE_OK = 0;
	const CODE_RESPONSE_FAILED = 1;

	const HEADER_LENGTH = 5;

	/**
	 * Reads a message from the given socket.
	 *
	 * The message is returned as an indexed array with keys "code" and "data".
	 *
	 * @param resource $socket the socket to read the message from.
	 * @return array the message read.
	 * @throws SocketException if an error occurs while reading the socket.
	 */
	public static function readMessage($socket) {
		$header = self::readSocket($socket, self::HEADER_LENGTH);
		$header = unpack("Ccode/VdataLength", $header);

		$data = self::readSocket($socket, $header["dataLength"]);

		return [ "code" => $header["code"], "data" => $data ];
	}

	/**
	 * Reads content from the given socket.
	 *
	 * It blocks until the specified number of bytes were read.
	 *
	 * @param resource $socket the socket to read the message from.
	 * @param int $length the number of bytes to read.
	 * @return string the content read.
	 * @throws SocketException if an error occurs while reading the socket.
	 */
	private static function readSocket($socket, $length) {
		if ($socket == null) {
			throw new SocketException("Null socket can not be read from");
		}

		$pendingLength = $length;
		$content = "";

		while ($pendingLength > 0) {
			$readContent = socket_read($socket, $pendingLength);
			if ($readContent === "") {
				throw new SocketException("Socket could not be read: $pendingLength bytes are pending, but there is no more data to read");
			} else if ($readContent == false) {
				throw new SocketException("Socket could not be read: " . socket_strerror(socket_last_error()));
			}

			$pendingLength -= strlen($readContent);
			$content = $content . $readContent;
		}

		return $content;
	}

	/**
	 * Writes a message to the given socket.
	 *
	 * @param resource $socket the socket to write the message to.
	 * @param int $code the message code.
	 * @param string $data the message data, if any.
	 * @throws SocketException if an error occurs while reading the socket.
	 */
	public static function writeMessage($socket, $code, $data = "") {
		if ($socket == null) {
			throw new SocketException("Null socket can not be written to");
		}

		$header = pack("CV", $code, strlen($data));

		$message = $header . $data;
		$pendingLength = strlen($message);

		while ($pendingLength > 0) {
			$sent = socket_write($socket, $message, $pendingLength);
			if ($sent !== 0 && $sent == false) {
				throw new SocketException("Message ($message) could not be written: " . socket_strerror(socket_last_error()));
			}

			$pendingLength -= $sent;
			$message = substr($message, $sent);
		}
	}
}

class FailedRequestException extends \Exception {
	public function __construct($message) {
		parent::__construct($message);
	}
}

/**
 * Server to handle requests sent by clients.
 */
class Server {

	/**
	 * @var port
	 */
	private $port;

	/**
	 * @param int $port
	 */
	public function __construct($port) {
		$this->port = $port;
	}

	/**
	 * Main loop to listen for and handle requests.
	 */
	public function main() {
		$mainSocket = socket_create_listen($this->port);
		if ($mainSocket === false) {
			throw new SocketException("Server socket to control Nextcloud server could not be created: " . socket_strerror(socket_last_error()));
		}

		while ($connectedSocket = socket_accept($mainSocket)) {
			try {
				$this->handleRequestAndSendResponse($connectedSocket);
			} catch (SocketException $exception) {
				echo "Error sending or receiving: " . $exception->getMessage() . "\n";
			} finally {
				socket_close($connectedSocket);
			}
		}

		socket_close($mainSocket);
	}

	/**
	 * Handles the request and sends the appropriate response.
	 *
	 * @param resource $socket the socket to use for communication.
	 * @throws SocketException if an error occurs when reading from or writing
	 *         to the socket.
	 */
	private function handleRequestAndSendResponse($socket) {
		try {
			$request = Messenger::readMessage($socket);

			$responseData = $this->handleRequest($request["code"], $request["data"]);

			Messenger::writeMessage($socket, Messenger::CODE_RESPONSE_OK, $responseData);
		} catch (FailedRequestException $exception) {
			echo "Error handling request: " . $exception->getMessage() . "\n";

			Messenger::writeMessage($socket, Messenger::CODE_RESPONSE_FAILED, $exception->getMessage());
		}
	}

	/**
	 * Handles the request, returning the response data (if any).
	 *
	 * @param string $code the request code.
	 * @param string $data the request data, if any.
	 * @return string the response data, if any.
	 * @throws FailedRequestException if the request can not be handled.
	 */
	private function handleRequest($code, $data) {
		if ($code == Messenger::CODE_REQUEST_RESET) {
			echo "Reset request\n";
			return $this->handleResetRequest();
		}

		echo "Unknown request: $code\n";
		throw new FailedRequestException("Unknown request: $code");
	}

	/**
	 * Resets the Nextcloud server to its default state.
	 *
	 * If the reset succeeds an empty string is returned. If it fails, a
	 * FailedRequestException is thrown.
	 *
	 * @return string empty.
	 * @throws FailedRequestException if the reset fails.
	 */
	private function handleResetRequest() {
		$this->execOrFailRequest("/usr/sbin/apache2ctl stop");

		$this->execOrFailRequest("cd /var/www/html/ && git reset --hard HEAD");
		$this->execOrFailRequest("cd /var/www/html/ && git clean -d --force");

		$this->execOrFailRequest("/usr/sbin/apache2ctl start");

		return "";
	}

	/**
	 * Executes the given command, throwing a FailedRequestException if it
	 * fails.
	 *
	 * @param string $command the command to execute.
	 * @throws FailedRequestException if the command fails to execute.
	 */
	private function execOrFailRequest($command) {
		exec($command . " 2>&1", $output, $returnValue);
		if ($returnValue != 0) {
			throw new FailedRequestException("'$command' could not be executed: " . implode("\n", $output));
		}
	}
}

}

namespace {

if (count($argv) != 2) {
	echo "Usage: " . $argv[0] . " PORT\n";

	return 1;
}

$port = $argv[1];

$server = new NextcloudServerControl\Server($port);
$server->main();

}
