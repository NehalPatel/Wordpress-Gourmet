<?php
//echo '<pre>';print_r($_SERVER);exit;
//$site_url = 'http://dev.everybodylovesitalian.local/';

//$file = dirname(__FILE__) .'/wp_reg_check.html';
//$fh = fopen($file, 'r');

//$theData = fread($fh, filesize($file));
//fclose($fh);


function minimize_css_js($theData){

	$included_css = array();

	/***/
	// create ignore CSS/JS array from html comments sections
	$pattern = '{<!\-\-(.*?)\-\->}s';
	$matches = array();
	preg_match_all($pattern, $theData, $matches);
	//print_r($matches);
	$ignore_css = array();
	$ignore_js = array();
	if(isset($matches[0])){
		foreach($matches[0] as $comment_html){
			$pattern = '/<(link)(?=.+?(?:type=["\'](text\/css)["\']|>))(?=.+?(?:media=["\'](.*?)["\']|>))(?=.+?(?:href=["\'](.*?)["\']|>))(?=.+?(?:rel="(.*?)"|>))[^>]+?\2[^>]+?(?:\/>|<\/style>)\s*/is';
			$css_matches = array();
			preg_match_all($pattern, $comment_html, $css_matches);
			if(isset($css_matches[4])){
				foreach($css_matches[4] as $ic){
					if(!empty($ic))
						$ignore_css[] = $ic;
				}
			}

			$pattern = '/<(script)(?=.+?(?:type=["\'](text.*?)["\']|>))(?=.+?(?:src=["\'](.*?)["\']|>))[^>]+?\2[^>]+?(?:\/>|.*?<\/script>)\s*/is';
			$js_matches = array();
			preg_match_all($pattern, $comment_html, $js_matches);
			if(!empty($js_matches[3])){
				foreach($js_matches[3] as $ij){
					if(!empty($ij))
						$ignore_js[] = $ij;
				}
			}
		}
	}


	$pattern = '/<(link)(?=.+?(?:type=["\'](text\/css)["\']|>))(?=.+?(?:media=["\'](.*?)["\']|>))(?=.+?(?:href=["\'](.*?)["\']|>))(?=.+?(?:rel="(.*?)"|>))[^>]+?\2[^>]+?(?:\/>|<\/style>)\s*/is';
	$matches = array();
	preg_match_all($pattern, $theData, $matches);

	/*echo '<pre>';
	print_r($matches);
	exit;*/
	$output = $theData;
	$cssReplace = '<link rel="stylesheet" type="text/css" href="'.plugins_url().'/wp-gourmet/tmp/wp_gourmet.css" media="screen" />';
	$cssFind = $cssReplace;
	//$jsReplace = '<script type="text/javascript" src="'.plugins_url().'/wp-gourmet/tmp/wp_gourmet.js"></script>';
	$jsReplace = '';
	//print "<pre>";
	$css_output = '';
	$css_content = '';
	foreach($matches[4] as $key => $cssfile){

		//echo "\n\n=======================================================\n";
		//echo "CSSFILE: ".$cssfile."\n----------------------------------------------\n";
		$cssfile = update_file_url($cssfile);
		//echo "Updated: ".$cssfile."\n----------------------------------------------\n";
		//continue;

		if(strtolower(substr($cssfile, 0, 4)) == 'http'){

			if(in_array(trim($cssfile), $ignore_css)){
				continue;
			}

			$included_css[md5($cssfile)] = md5($cssfile);
			$css_content = "\n/*".$cssfile."*/\n".get_raw_content($cssfile);

			$css_content = update_imported_css($cssfile, $css_content);

			//echo "\n\n=======================================================\n";
			//echo "CSSFILE:".$cssfile."\n----------------------------------------------\n";

			//images without quote
			$arrCSSMatch = array();
			preg_match_all('!url\(([^\'"\(\)]+?)\)!s', $css_content,$arrCSSMatch);
			//print_r($arrCSSMatch);

			if(!empty($arrCSSMatch)){
				$css_content = update_images_path($cssfile, $css_content, $arrCSSMatch);
			}
			//echo $css_content;exit;

			//images with quote
			$arrCSSMatch = array();
			preg_match_all('!url\(["\'](.*?)["\']\)!s', $css_content,$arrCSSMatch);
			//print_r($arrCSSMatch);
			if(!empty($arrCSSMatch)){
				$css_content = update_images_path($cssfile, $css_content, $arrCSSMatch);
			}

			$output = str_replace(trim($matches[0][$key]), $cssReplace, $output);
			$cssReplace="";

			$css_output .= $css_content;
		}
	}

	$css_output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css_output);
	$css_output = str_replace(': ', ':', $css_output);
	$css_output = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '        '), '', $css_output);

	asort($included_css);
	$css_file_name = md5(implode(',', $included_css));
	$new_replace = '<link rel="stylesheet" type="text/css" href="'.plugins_url().'/wp-gourmet/tmp/'.$css_file_name.'.css" media="screen" async="true" />';
	$output = str_replace($cssFind, $new_replace, $output);
	$fcss = fopen(dirname(__FILE__) . '/tmp/'.$css_file_name.'.css', w);
	fwrite($fcss, $css_output);
	fclose($fcss);


	$pattern = '/<(script)(?=.+?(?:type=["\'](text.*?)["\']|>))(?=.+?(?:src=["\'](.*?)["\']|>))[^>]+?\2[^>]+?(?:\/>|.*?<\/script>)\s*/is';
	$matches = array();
	preg_match_all($pattern, $theData, $matches);

	$js_content = '';
	$js_content_u = '';

	foreach($matches[3] as $key => $jsfile){
		if(strtolower(substr($jsfile, 0, 4)) == 'http'){

			if(in_array(trim($jsfile), $ignore_js)){
				//$js_content .= "\n/* SKIPPED:".$jsfile."*/\n";
				continue;
			} else {

				 if(preg_match("!/jquery([\.\-]?min[\.\-]?)?[\.0-9\-]*?([\.\-]?min[\.\-]?)?\.js!i", $jsfile)){
				 	//$js_content_u .= "\n/*".$jsfile."*/\n".file_get_contents($jsfile);
				 	continue;
				 }
				// }else{
				// 	$js_content .= "\n/*".$jsfile."*/\n".file_get_contents($jsfile);
				// }

				$js_content = "\n<script>/*".$jsfile."*/\n".file_get_contents($jsfile) .'</script>';
				$output = str_replace(trim($matches[0][$key]), $js_content, $output);
			}
		}
	}

	/*$inline_javacript = "\n" . 'jQuery(window).load(function() {';
	$matches = array();
	$pattern = '!<script[^>]*>(.*?)</script>!is';
	preg_match_all($pattern, $output, $matches);
	if(!empty($matches[1])){
		$matches[1] = array_filter($matches[1]);
		if(!empty($matches[1])){
			foreach($matches[1] as $key => $inline_js){
				$inline_js = trim($inline_js);
				if( empty($inline_js) || preg_match('!type=\'application/.*?\+json\'!i', trim($matches[0][$key]))){
					continue;
				}
				//$js_content .= "\n try{".$inline_js."}catch(np){};";
				$inline_javacript .= "\n ".$inline_js;
				if(substr(trim($inline_js), -1) != ';'){
					$js_content .=';';
				}
				$output = str_replace(trim($matches[0][$key]), '', $output);
			}
		}
	}
	$inline_javacript .= '});';

	$js_content .= $inline_javacript;*/


	/*$output = preg_replace('!</body(.*?)>!s', $jsReplace .'</body\1>', $output);
	$fjs = fopen(dirname(__FILE__) . '/tmp/wp_gourmet.js', w);

	//$js_content = "jQuery(document).ready(function($) {\n".$js_content."\n});";
	fwrite($fjs, $js_content_u . "\n\n" .$js_content);
	fclose($fjs);*/

	preg_match_all('!<img.*?(src[^=]*=([^\'" ]+))[ ]*[^>]+>!i', $output, $arrMatchs1, PREG_SET_ORDER);
	preg_match_all('!<img.*?(src[ \t]*=[ ]*["\']([^"\']+)["\'])[ ]*[^>]+>!i', $output, $arrMatchs, PREG_SET_ORDER);
	$arrMatchs = $arrMatchs1+$arrMatchs;
	foreach($arrMatchs as $k => $arrImages){
		$arrImages[2]=trim($arrImages[2]);
		$output = str_replace($arrImages[1], "data-src='".$arrImages[2]."' src='".plugins_url()."/wp-gourmet/blank_transp.png'", $output);
	}

	$output = preg_replace("![\t ]+!", ' ', $output);
	$output = preg_replace("![\n\r]+!", "\n", $output);

	return $output;
}


function update_file_url($file_name){
	//ignore if URL start from http or https
	if(strtolower(substr($file_name, 0, 4)) == 'http' || strtolower(substr($file_name, 0, 5)) == 'https'){
		return $file_name;
	}

	//if URL start from "//" add host_schema (http/https)
	$protocol = 'http';
	if($_SERVER['SERVER_PORT'] == '443')
		$protocol .= 's';
	$protocol .= ':';

	//if URL start without host_schema, add it
	if(substr($file_name, 0, 2) == '//'){
		return $protocol . $file_name;
	}

	//if relative path add site url
	if(substr($file_name, 0, 1) != '/'){
		$file_name = $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']).'/'.$file_name;
		$file_name = $protocol . '//' . str_replace('//', '/', $file_name);
		return $file_name;
	}
}

function get_raw_content($url){
	if(get_http_response_code($url) == "200"){
	    return file_get_contents($url);
	}
	return '';
}
function get_http_response_code($url) {
   $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}

function update_images_path($cssfile, $css_content, $arrCSSMatch){
	if(empty($arrCSSMatch[1]))
		return $css_content;

	$arrUrl = parse_url($cssfile);
	$arrUrl['path_only']=dirname($arrUrl['path']);
	//print_r($arrUrl);

	// echo '<pre>';
	// print_r($arrCSSMatch);
	// echo '</pre>';


	//$arrCSSMatch[1] = array_unique($arrCSSMatch[1]);

	foreach($arrCSSMatch[1] as $key => $file_name){

		if(strtolower(substr($file_name, 0, 4)) == 'http' || strtolower(substr($file_name, 0, 5)) == 'https')
			continue;

		if(substr($file_name, 0, 2) == '//'){
			$file_full_name = $arrUrl['scheme'] .':' . $file_name;
			//$css_content = str_replace($file_name, $arrUrl['scheme'] .':' . $file_name, $css_content);
		} else if(substr($file_name, 0, 1) != '/'){
			//echo "\n filename:".$file_name ."\n";
			$file_full_name = $arrUrl['scheme'] . '://' . str_replace('//', '/', $arrUrl['host'] . $arrUrl['path_only'] . '/' .$file_name);
		}

		if(! preg_match("!data:application!", $arrCSSMatch[0][$key])){
			$css_content = str_replace($arrCSSMatch[0][$key], 'url("'.$file_full_name.'")', $css_content);
		}
	}

	return $css_content;
}

function update_imported_css($cssfile, $css_content){

	$arrCSSMatch = array();
	preg_match_all('!@import ["\'](.*?)["\']!s', $css_content,$arrCSSMatch);
	if(!isset($arrCSSMatch[1]))
		return $css_content;

	foreach($arrCSSMatch[1] as $key => $import_file){
		$parent_file = update_file_url($cssfile);
		$import_full_file = get_raw_content(dirname($parent_file) .'/'.$import_file);
		$css_content = str_replace($arrCSSMatch[0][$key], "\n/*".$import_file."*/\n" . $import_full_file, $css_content);
	}
	//echo $css_content;exit;
	return $css_content;
}