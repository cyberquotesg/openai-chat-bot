<!DOCTYPE html>
<html>

<?php
	include "library.php";

	$active_thread = !empty($_GET["thread_id"]) ? library::get_thread($_GET["thread_id"]) : null;
	$new = empty($active_thread);
?>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>OpenAI Chat Bot</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<style type="text/css">
	#threads
	{
		height: calc(100vh - 240px + 65px);
		padding-right: 12px;
		overflow-y: scroll;
	}
	#threads a
	{
		width: 100%;
	}
	#threads a#new-thread
	{
		cursor: pointer;
	}
	#threads a.existing-thread
	{
		margin-top: 12px;
	}
	#chat-room
	{
		height: calc(100vh - 240px);
		padding-right: 12px;
		overflow-y: scroll;
	}
	#chat-room .chat
	{
		width: calc(100% - 62px - 12px);
	    padding: 12px;
		margin-bottom: 12px;
	    border: 1px solid grey;
	    border-radius: 4px;
	}
	#chat-room .chat.user
	{
		text-align: right;
    	margin-left: calc(62px + 12px);
	}
	#chat-room .chat.assistant
	{
		text-align: left;
		margin-right: calc(62px + 12px);
	}
	#chat-control
	{
		display: flex;
		justify-content: space-between;
	}
	#chat-control #chat-field
	{
		width: calc(100% - 62px - 12px);
		height: 65px;
	}
	#chat-control #chat-send
	{
		width: 62px;
	}
	</style>

	<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
	<script type="text/javascript">
		jQuery(function($){
			function insertNewMessage(message)
			{
				var html = '';
				html += '<div class="chat ' + message.role + '">';
					html += '<small>' + message.created + '</small><br />' + message.content;
				html += '</div>';

				$("#chat-room").append(html);
				$("#chat-room").animate({ scrollTop: $("#chat-room").height() }, 1000);
			}
			function checkReply()
			{
				$.get("./api.php?function=check_reply&params[]=<?= $active_thread["thread_id"] ?>", function(reply){
					if (reply.code == 2)
					{
						for (var message of reply.messages)
						{
							insertNewMessage(message);
						}
					}

					setTimeout(checkReply, 1000);
				}, "json");
			}

			// scroll down
			$("#chat-room").animate({ scrollTop: $("#chat-room").height() }, 0);

			// if this is not new, then periodically check new chats
			<?php if (!$new): ?>
				checkReply();
			<?php endif; ?>

			// new thread
			$("#chat-send").click(function(){
				var value = $("#chat-field").val();

				// if it is new
				if ($("#chat-room").attr("data-thread-id") == "null")
				{
					$.get("./api.php?function=create_new_thread&params[]", function(thread){
						$.post("./api.php?function=post_new_message", {
							params: [
								thread["thread_id"],
								$("#chat-field").val(),
							],
						}, function(message){
							window.location = "./index.php?thread_id=" + thread["thread_id"];
						}, "json");
					}, "json");
				}
				// if existing
				else
				{
					$.post("./api.php?function=post_new_message", {
						params: [
							$("#chat-room").attr("data-thread-id"),
							$("#chat-field").val(),
						],
					}, function(message){
						$("#chat-field").val("");
						insertNewMessage(message);
					}, "json");
				}
			});
		});
	</script>
</head>

<body>

	<h1 class="text-center my-4">OpenAI Chat Bot</h1>

	<div class="container">
		<div class="row">
			<div class="col-3">
				<h4 class="text-center mb-4">Sessions</h4>
				<div id="threads">
					<a id="new-thread" class="btn <?= $new ? "btn-primary" : "btn-outline-primary" ?>" href="./index.php">New Thread</a>

					<?php $threads = library::get_threads(); ?>
					<?php foreach ($threads as $thread): ?>
						<a class="btn existing-thread <?= $active_thread["thread_id"] == $thread["thread_id"] ? "btn-secondary" : "btn-outline-secondary" ?>" href="./index.php?thread_id=<?= $thread["thread_id"] ?>">Thread <?= $thread["created"] ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="col-9">
				<h4 class="text-center mb-4">Chat Room</h4>
				<div id="chat-room" data-thread-id="<?= $new ? "null" : $active_thread["thread_id"] ?>">
					<?php if (!$new): ?>
						<?php $messages = library::get_messages($active_thread["thread_id"]); ?>
						<?php foreach ($messages as $message): ?>
							<div class="chat <?= $message["role"] ?>">
								<small><?= $message["created"] ?></small><br /><?= $message["content"] ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
  				<div id="chat-control" class="form-group">
					<textarea id="chat-field" class="form-control"></textarea>
					<button id="chat-send" class="btn btn-primary">Send</button>
  				</div>
			</div>
		</div>
	</div>

</body>
</html>