<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>OpenAI Chat Bot</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<style type="text/css">
	#new-thread
	{
		cursor: pointer;
	}
	</style>

	<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
	<script type="text/javascript">
		jQuery(function($){
			// get threads
			$.get("./api.php?function=get_thread_list&params[]", function(threads){
				for (var thread of threads)
				{
					$("<div>").text(thread.created).insertAfter("#new-thread");
				}
			}, "json");

			// new thread
			$("#new-thread").click(function(){
			});
		});
	</script>
</head>

<?php
	include "library.php";
?>

<body>

	<h1 class="text-center">OpenAI Chat Bot</h1>

	<div class="container">
		<div class="row">
			<div class="col-2">
				<h4>Sessions</h4>
				<div id="threads">
					<div id="new-thread">New Thread</div>
				</div>
			</div>
			<div class="col-10">
				<div id="chat-room"></div>
			</div>
		</div>
	</div>

</body>
</html>