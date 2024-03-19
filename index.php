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
	<title>OpenAI ChatBot</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<style type="text/css">
	#threads
	{
		height: calc(100vh - 275px + 65px);
		padding-right: 24px;
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
		height: calc(100vh - 275px);
		padding-right: 24px;
		overflow-y: scroll;
	}
	#chat-room .chat
	{
		width: calc(100% - 62px - 24px);
	    padding: 12px;
		margin-bottom: 12px;
	    border: 1px solid grey;
	    border-radius: 4px;
	}
	#chat-room .chat.user
	{
		text-align: right;
    	margin-left: calc(62px + 24px);
	}
	#chat-room .chat.assistant
	{
		text-align: left;
		margin-right: calc(62px + 24px);
	}
	#chat-control
	{
		display: flex;
		justify-content: space-between;
		flex-wrap: wrap;
	}
	#chat-control #chat-field
	{
		width: 100%;
		height: 65px;
	}
	#chat-control #chat-file
	{
		width: calc(100% - 62px - 24px);
		margin-top: 12px;
	}
	#chat-control #chat-send
	{
		width: 62px;
		margin-top: 12px;
	}
	#chat-control #chat-send:not(.sending) .secondary-text
	{
		display: none;
	}
	#chat-control #chat-send.sending .primary-text
	{
		display: none;
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
				$("#chat-room").animate({ scrollTop: $("#chat-room").prop('scrollHeight') }, 1000);
			}
			function checkReply()
			{
				var thread_id = $("#chat-control [name='thread_id']").val();

				// if thred is not set up, then just skip
				if (!thread_id)
				{
					setTimeout(checkReply, 1000);
				}

				// if alrady set up, then do it
				else
				{
					$.get("./api.php?function=check_reply&thread_id=" + thread_id, function(reply){
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
			}
			function sendMessage(callback)
			{
				$.ajax({
					// Your server script to process the upload
					url: "./api.php?function=post_new_message",
					type: "POST",

					// Form data
					data: new FormData($("#chat-control")[0]),
					dataType: "json",

					// Tell jQuery not to process data or worry about content-type
					// You *must* include these options!
					cache: false,
					contentType: false,
					processData: false,

					success: callback,
				});
			}

			// scroll down
			$("#chat-room").animate({ scrollTop: $("#chat-room").prop('scrollHeight') }, 0);

			// periodically check new chats
			checkReply();

			// send message
			$("#chat-control").submit(function(e){
				e.preventDefault();
			});
			$("#chat-send").click(function(){
				$("#chat-send").addClass("sending");
				var thread_id = $("#chat-control [name='thread_id']").val();

				// if it is new
				if (!thread_id)
				{
					$.get("./api.php?function=create_new_thread", function(thread){
						$("#chat-control [name='thread_id']").val(thread.thread_id);

						sendMessage(function(message){
							$("#chat-field").val("");
							insertNewMessage(message);
							$("#chat-send").removeClass("sending");

							// active buttons should be deactivated
							$("#threads .btn-primary")
								.removeClass("btn-primary")
								.addClass("btn-outline-primary");
							$("#threads .btn-secondary")
								.removeClass("btn-secondary")
								.addClass("btn-outline-secondary");

							// insert session button
							$("<a>")
								.addClass("btn existing-thread btn-secondary")
								.attr("href", "./index.php?thread_id=" + thread.thread_id)
								.text("Thread " + thread.created)
								.insertAfter("#new-thread");

							// change the url
							window.history.replaceState(null, "", "index.php?thread_id=" + thread.thread_id);
						});
					}, "json");
				}
				// if existing
				else
				{
					sendMessage(function(message){
						$("#chat-field").val("");
						insertNewMessage(message);
						$("#chat-send").removeClass("sending");
					});
				}
			});
		});
	</script>
</head>

<body>

	<h1 class="text-center my-4">OpenAI ChatBot</h1>

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
				<div id="chat-room">
					<?php if (!$new): ?>
						<?php $messages = library::get_messages($active_thread["thread_id"]); ?>
						<?php foreach ($messages as $message): ?>
							<div class="chat <?= $message["role"] ?>">
								<small><?= $message["created"] ?></small><br /><?= $message["content"] ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
  				<form id="chat-control" class="form-group">
					<input type="hidden" name="thread_id" value="<?= $new ? "" : $active_thread["thread_id"] ?>" />
					<textarea id="chat-field" class="form-control" name="message"></textarea>
					<input id="chat-file" type="file" name="file_path" />
					<button id="chat-send" class="btn btn-primary" type="button">
						<span class="primary-text">Send</span>
						<div class="secondary-text spinner-border spinner-border-sm" role="status"></div>
					</button>
  				</form>
			</div>
		</div>
	</div>

</body>
</html>