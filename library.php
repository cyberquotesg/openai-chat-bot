<?php

class library
{
	private static $openai_key = "";
	private static $assistant_id = "";

	// ================================================================================================== helper
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
		$servername = "localhost";
		$username = "root";
		$password = "";
		$dbname = "openai-chat-bot";

		$connection = mysqli_connect($servername, $username, $password, $dbname);
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
	// done
	public static function create_message($thread_id, $message)
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

	// ================================================================================================== communication with front end
	// done
	public static function create_new_thread()
	{
		// create thread
		$thread = self::create_thread();

		// save thread to db
		self::db("
			INSERT INTO openai_thread (created, thread_id, run_id, run_status)
			VALUES ('" . $thread["created_at"] . "', '" . $thread["id"] . "', '', '')
		");

		return $thread;
	}
	// done
	public static function post_new_message($thread_id, $message)
	{
		// create message
		$message = self::create_message($thread_id, $message);

		// save message to db
		self::db("
			INSERT INTO openai_message (created, thread_id, message_id, role, content)
			VALUES ('" . $message["created_at"] . "', '" . $message["thread_id"] . "', '" . $message["id"] . "', '" . $message["role"] . "', '" . $message["content"][0]["text"]["value"] . "')
		");

		// create run
		$run = self::create_run($thread_id);

		// insert run to thread record
		self::db("
			UPDATE openai_thread
			SET run_id = '" . $run["id"] . "', run_status = '" . $run["status"] . "'
			WHERE id = '" . $run["thread_id"] . "'
		");

		return $message;
	}
	// done
	public static function check_reply($thread_id, $run_id)
	{
		// get run
		$run = self::retrieve_run($thread_id, $run_id);

		// completed
		if ($run["status"] == "completed")
		{
			// get message listing
			$data = self::enlist_message($thread_id, 5);

			// prepare the data from openai
			$messages = [];
			foreach ($data["data"] as $message)
			{
				if ($message["role"] == "assistant") $messages[] = $message;
				else break;
			}
			array_reverse($messages);

			// save to db
			foreach ($messages as $message)
			{
				$reply = str_replace("'", "\\'", $message["content"][0]["text"]["value"]);
				self::db("
					INSERT INTO openai_message (created, thread_id, message_id, role, content)
					VALUES ('" . $message["created_at"] . "', '" . $message["thread_id"] . "', '" . $message["id"] . "', '" . $message["role"] . "', '" . $reply . "')
				");
			}

			return [
				"code" => 1,
				"messages" => $messages,
			];
		}

		// stopped
		else if (in_array($run["status"], ["cancelled", "failed", "expired"]))
		{
			return [
				"code" => 0,
			];
		}

		// not finish yet, just wait
		else
		{
			return [
				"code" => 1,
			];
		}
	}
	// done
	public static function get_threads()
	{
		// get threads from db
		$threads = self::db("
			SELECT *
			FROM openai_thread
		");

		return $threads;
	}
}

?>