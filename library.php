<?php

/******************************** database /

--
-- Database: `openai-chatbot`
--
CREATE DATABASE IF NOT EXISTS `openai-chatbot` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `openai-chatbot`;

-- --------------------------------------------------------

--
-- Table structure for table `file`
--

DROP TABLE IF EXISTS `openai_chatbot_file`;
CREATE TABLE IF NOT EXISTS `openai_chatbot_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` varchar(25) NOT NULL,
  `file_id` varchar(100) NOT NULL,
  `file_name` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `openai_chatbot_message`;
CREATE TABLE IF NOT EXISTS `openai_chatbot_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` varchar(25) NOT NULL,
  `thread_id` varchar(100) NOT NULL,
  `message_id` varchar(100) NOT NULL,
  `file_id` varchar(100) NOT NULL,
  `role` varchar(25) NOT NULL,
  `content` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `openai_chatbot_thread`
--

DROP TABLE IF EXISTS `openai_chatbot_thread`;
CREATE TABLE IF NOT EXISTS `openai_chatbot_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` varchar(25) NOT NULL,
  `thread_id` varchar(100) NOT NULL,
  `run_id` varchar(100) NOT NULL,
  `run_status` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/**/
class library
{
	private static $openai_key = "";
	private static $assistant_id = "";
	private static $servername = "";
	private static $username = "";
	private static $password = "";
	private static $dbname = "";

	// ================================================================================================== helper
	// done
    public static function get_time_object($time_string = "now", $timezone = "Asia/Singapore")
    {
        global $DB;

        $timestamp = null;
        if (is_numeric($time_string))
        {
            $timestamp = (int) $time_string;
            $time_string = "now";
        }

        $timezone = new \DateTimeZone($timezone);
        $time_object = new \DateTime($time_string, $timezone);

        return $timestamp ? $time_object->setTimestamp($timestamp) : $time_object;
    }
	// done
	private static function params_to_uri($limit = null, $order = null, $after = null, $before = null)
	{
	    $uri = [];

	    if ($limit) $uri[] = "limit=" . $limit;
	    if ($order) $uri[] = "order=" . $order;
	    if ($after) $uri[] = "after=" . $after;
	    if ($before) $uri[] = "before=" . $before;

	    if (empty($uri)) $uri = "";
	    else $uri = "?" . implode("&", $uri);

	    return $uri;
	}
	// done
	private static function db($sql)
	{
		$connection = mysqli_connect(self::$servername, self::$username, self::$password, self::$dbname);
		if (!$connection) die("Connection failed: " . mysqli_connect_error());

		$data = [];
		$result = mysqli_query($connection, $sql);

		// only for select
		$sql = trim($sql);
		$sql = strtolower($sql);
		$sql = explode(" ", $sql);
		if ($sql[0] == "select")
		{
			if (mysqli_num_rows($result) > 0) while($row = mysqli_fetch_assoc($result)) $data[] = $row;
		}

		mysqli_close($connection);

		return $data;
	}

	// ================================================================================================== communication with openai
	// done
	public static function create_thread()
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json",
			"Authorization: Bearer " . self::$openai_key,
			"OpenAI-Beta: assistants=v1",
		]);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "");

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		return json_decode($result, true);
	}
	public static function create_file($file_path)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/files");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Authorization: Bearer " . self::$openai_key,
		]);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "file" => new CURLFile($file_path),
            "purpose" => "assistants",
        ]);

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		return json_decode($result, true);
	}
	// done
	public static function create_message($thread_id, $message, $file_id = null)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads/" . $thread_id . "/messages");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json",
			"Authorization: Bearer " . self::$openai_key,
			"OpenAI-Beta: assistants=v1",
		]);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
			"role" => "user",
			"content" => $message,
			"file_ids" => $file_id ? [$file_id] : [],
		]));

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		return json_decode($result, true);
	}
	// done
	public static function create_run($thread_id)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads/" . $thread_id . "/runs");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json",
			"Authorization: Bearer " . self::$openai_key,
			"OpenAI-Beta: assistants=v1",
		]);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
			"assistant_id" => self::$assistant_id,
		]));

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		return json_decode($result, true);
	}
	// done
	public static function retrieve_run($thread_id, $run_id)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $run_id);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Authorization: Bearer " . self::$openai_key,
			"OpenAI-Beta: assistants=v1",
		]);

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		return json_decode($result, true);
	}
	// done
	public static function enlist_message($thread_id, $limit = null, $order = null, $after = null, $before = null)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads/" . $thread_id . "/messages" . self::params_to_uri($limit, $order, $after, $before));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json",
			"Authorization: Bearer " . self::$openai_key,
			"OpenAI-Beta: assistants=v1",
		]);

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		return json_decode($result, true);
	}

	// ================================================================================================== communication with database
	// done
	public static function get_threads()
	{
		// get threads from db
		$threads = self::db("
			SELECT *
			FROM openai_chatbot_thread
			ORDER BY id DESC
		");

		return $threads;
	}
	// done
	public static function get_thread($thread_id)
	{
		// get threads from db
		$threads = self::db("
			SELECT *
			FROM openai_chatbot_thread
			WHERE thread_id = '" . $thread_id . "'
		");

		return $threads[0];
	}
	// done
	public static function get_messages($thread_id)
	{
		// get messages from db
		$messages = self::db("
			SELECT m.*, f.file_name
			FROM openai_chatbot_message m
			LEFT JOIN openai_chatbot_file f ON f.file_id = m.file_id
			WHERE m.thread_id = '" . $thread_id . "'
		");

		return $messages;
	}

	// ================================================================================================== communication with front end
	// done
	public static function create_new_thread()
	{
		// create thread
		$thread = self::create_thread();

		// adjust data from openai to fit with db
		$thread = [
			"created" => self::get_time_object($thread["created_at"])->format("Y/m/d H:i:s"),
			"thread_id" => $thread["id"],
			"run_id" => "",
			"run_status" => "",
		];

		// save thread to db
		self::db("
			INSERT INTO openai_chatbot_thread (created, thread_id, run_id, run_status)
			VALUES ('" . $thread["created"] . "', '" . $thread["thread_id"] . "', '" . $thread["run_id"] . "', '" . $thread["run_status"] . "')
		");

		return $thread;
	}
	// done
	public static function post_new_message($thread_id, $message, $file_path = null)
	{
		// file is provided
		if ($file_path)
		{
			// upload file
			$file = self::create_file($file_path);

			// adjust data from openai to fit with db
			$file = [
				"created" => self::get_time_object($file["created_at"])->format("Y/m/d H:i:s"),
				"file_id" => $file["id"],
				"file_name" => $file["filename"],
			];

			// save file to db
			self::db("
				INSERT INTO openai_chatbot_file (created, file_id, file_name)
				VALUES ('" . $file["created"] . "', '" . $file["file_id"] . "', '" . $file["file_name"] . "')
			");
		}
		else
		{
			$file = [
				"created" => "",
				"file_id" => "",
				"file_name" => "",
			];
		}

		// create message
		$message = self::create_message($thread_id, $message, $file["file_id"]);

		// adjust data from openai to fit with db
		$message = [
			"created" => self::get_time_object($message["created_at"])->format("Y/m/d H:i:s"),
			"thread_id" => $message["thread_id"],
			"message_id" => $message["id"],
			"file_id" => $file["file_id"],
			"file_name" => $file["file_name"],
			"role" => $message["role"],
			"content" => $message["content"][0]["text"]["value"],
		];

		// save message to db
		self::db("
			INSERT INTO openai_chatbot_message (created, thread_id, message_id, file_id, role, content)
			VALUES ('" . $message["created"] . "', '" . $message["thread_id"] . "', '" . $message["message_id"] . "', '" . $message["file_id"] . "', '" . $message["role"] . "', '" . str_replace("'", "\\'", $message["content"]) . "')
		");

		// create run
		$run = self::create_run($thread_id);

		// insert run to thread record
		self::db("
			UPDATE openai_chatbot_thread
			SET run_id = '" . $run["id"] . "', run_status = '" . $run["status"] . "'
			WHERE thread_id = '" . $run["thread_id"] . "'
		");

		return $message;
	}
	// done
	public static function check_reply($thread_id)
	{
		// get thread
		$thread = self::get_thread($thread_id);

		// if the thread is not running then stop
		if (empty($thread["run_id"]))
		{
			return [
				"code" => 1,
			];
		}

		// get run
		$run = self::retrieve_run($thread_id, $thread["run_id"]);

		// completed
		if ($run["status"] == "completed")
		{
			// get message listing
			$data = self::enlist_message($thread_id, 10);

			// adjust data from openai to fit with db
			$messages = [];
			foreach ($data["data"] as $message)
			{
				if ($message["role"] == "assistant")
				{
					$messages[] = [
						"created" => self::get_time_object($message["created_at"])->format("Y/m/d H:i:s"),
						"thread_id" => $message["thread_id"],
						"message_id" => $message["id"],
						"role" => $message["role"],
						"content" => $message["content"][0]["text"]["value"],
					];
				}

				else break;
			}
			$messages = array_reverse($messages);

			// save to db
			foreach ($messages as $message)
			{
				self::db("
					INSERT INTO openai_chatbot_message (created, thread_id, message_id, file_id, role, content)
					VALUES ('" . $message["created"] . "', '" . $message["thread_id"] . "', '" . $message["message_id"] . "', '', '" . $message["role"] . "', '" . str_replace("'", "\\'", $message["content"]) . "')
				");
			}

			// clean up thread record
			self::db("
				UPDATE openai_chatbot_thread
				SET run_id = '', run_status = '" . $run["status"] . "'
				WHERE thread_id = '" . $thread_id . "'
			");

			return [
				"code" => 2,
				"messages" => $messages,
			];
		}

		// stopped
		else if (in_array($run["status"], ["cancelled", "failed", "expired"]))
		{
			// clean up thread record
			self::db("
				UPDATE openai_chatbot_thread
				SET run_id = '', run_status = '" . $run["status"] . "'
				WHERE thread_id = '" . $thread_id . "'
			");

			return [
				"code" => 0,
			];
		}

		// not finish yet, just wait
		else
		{
			// clean up thread record
			self::db("
				UPDATE thread
				SET run_status = '" . $run["status"] . "'
				WHERE thread_id = '" . $thread_id . "'
			");

			return [
				"code" => 1,
			];
		}
	}
}

?>