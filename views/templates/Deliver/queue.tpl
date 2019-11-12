<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?=__('Queue');?></title>

    <link rel="stylesheet" href="<?= ROOT_URL ?>tao/views/css/tao-main-style.css">
    
    <script>
    function loadDoc(url, cFunction) {
	  var xhttp;
	  req=new XMLHttpRequest();

	  req.responseType = 'json';
		req.open('GET', url, true);
		req.onload  = function() {
		   var jsonResponse = req.response;
		   cFunction(jsonResponse);
		};
		req.send(null);
	}
	function myFunction(json) {
	  if (json.status == 1) {
	  	window.location = "<?= _url('launch', null, null, ['ticket' => $ticketId]) ?>";
	  }
	}
	
	setInterval(function() { loadDoc('<?= _url('ticket', null, null, ['id' => $ticketId]) ?>', myFunction)}, 20000);
    </script>
</head>

<body class="tao-scope">
    <div style="padding: 0 0 10px 0">
    	Welcome to the Queue
    </div>
        
</body>
