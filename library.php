<?php

class library
{
	private static $openai_key = "";

	public static function create_thread()
	{
		$ch = curl_init();

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
		curl_close($ch);

		return json_decode($result, true);
	}
}

?>