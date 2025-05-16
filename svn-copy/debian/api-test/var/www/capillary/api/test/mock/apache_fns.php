<?

// Mock request headers fn for cli php
function apache_request_headers()
{
	global $request_headers;
	if(isset($request_headers))
	{
		return $request_headers;
	}
	else
		return null;
}

?>
