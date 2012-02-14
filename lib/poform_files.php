<?php


// to update this...
$file_types = array ('image/gif','image/jpeg','image/pjpeg','image/png');


$field_name = key($_FILES);

if(in_array($_FILES[$field_name]["type"],$file_types)){
	if($_FILES[$field_name]["size"] < 20000){
	  if ($_FILES[$field_name]["error"] > 0)
		{
		echo "Return Code: " . $_FILES[$field_name]["error"] . "<br />";
		}
	  else
		{
		echo "Upload: " . $_FILES[$field_name]["name"] . "<br />";
		echo "Type: " . $_FILES[$field_name]["type"] . "<br />";
		echo "Size: " . ($_FILES[$field_name]["size"] / 1024) . " Kb<br />";
		echo "Temp file: " . $_FILES[$field_name]["tmp_name"] . "<br />";
		if (file_exists(IMAGE_DIR . $_FILES[$field_name]["name"]))
		  {
		  echo $_FILES[$field_name]["name"] . " already exists. ";
		  $_POST[$field_name] = $_FILES[$field_name]["name"];
		  print_r($_POST);
		  unset($_FILES[$field_name]);
		  
		  }
		else
		  {
		  if(move_uploaded_file($_FILES[$field_name]["tmp_name"],  IMAGE_DIR . $_FILES[$field_name]["name"]) ){
			  echo "Stored in: " . IMAGE_DIR . $_FILES[$field_name]["name"];
			  $_POST[$field_name] = $_FILES[$field_name]["name"];
			  
			  print_r($_POST);
			  unset($_FILES[$field_name]);
			  }
		   else
			   echo 'Did not move file from temp.. check permissions on "' . IMAGE_DIR .'"';
		  }
		}


			
	}else{
		echo "File too large";
	}

}else{
	echo "Invalid image file";
}

if ((($_FILES[$field_name]["type"] == "")
|| ($_FILES[$field_name]["type"] == "image/jpeg")
|| ($_FILES[$field_name]["type"] == "image/pjpeg"))
&& ($_FILES[$field_name]["size"] < 20000))
  {
  }
else
  {
  echo "Invalid file";
  }


?>

