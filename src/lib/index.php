<?php
  include_once( __DIR__ . '/../parseini.php');
  include_once( __DIR__ . '/../vzcommons.php');
	include( "assets/userauth.php");
  include_once( __DIR__ . '/../parse_access.php');

  $xapplication = "";
  $xcategory = "";
  $xfeature_index = 0;
  $xfeature_text = "";
  $xfeature_path = "";
  $xfilename = "";
  $xnew_category = "";
  $xaction = "";
  $error_msg = "";
  $success_msg = "";
  $xconfig = '';
  $xcontext = '';
  $xconfig_path = '';
  
  if( !empty($_GET['token'])) {
	$token = $_GET['token'];
  } else {
	$token = @$_POST['token'];
  }

  if( !empty($_POST['application']) ) {
    $xapplication = trim($_POST['application']);
    $app = $xapplication;
  }

  if( !empty($_POST['category']) ) {
    $xcategory = trim($_POST['category']);
  }

  if( !empty($_POST['new_category']) ) {
    $xnew_category = trim($_POST['new_category']);
  }

  if( !empty($_POST['feature_index']) ) {
    $xfeature_index = $_POST['feature_index'];
  }

  if( !empty($_POST['feature_text']) ) {
    $xfeature_text = trim($_POST['feature_text']);
  }

  if( !empty($_POST['feature_path']) ) {
    $xfeature_path = trim($_POST['feature_path']);
  }

  if( !empty($_POST['filename']) ) {
    $xfilename = trim($_POST['filename']);
  }

  if( !empty($_POST['action']) ) {
    $xaction = trim($_POST['action']);
  }


  $features = array();
  $feature_categories = array();
  $feature_list = array();
  $feature_path_all = array();
  $feature_path = array();
  $feature_title = array();

// echo "[" . $token . "]<br>";
// echo "[" . $_SESSION['token'] . "]<br>";

  if( !empty($xaction)) {
    if( empty($token) || $token != $_SESSION['token'] ) {
      echo "Session expired ....";
      exit;
    }
  }
  
  if( $xaction == "add_category" ) {
    if( preg_match( "/^[a-z0-9][a-z0-9_]+[a-z0-9]$/", trim($xnew_category)) != 1 ) {
      $error_msg = "New category name contains unacceptable characters.";
    } else {
      $xnew_category = str_replace( ' ', '_', trim($xnew_category));
      $dir_name = $test_path . "/features/" . $xnew_category;
      if( is_dir( $dir_name)) {
        $error_msg = "New category name exists!";
      } else {
        if( mkdir( $dir_name) === FALSE ) {
          $error_msg = "Could not create Category. Check folder permission.";
        } else {
          $success_msg = "New category has been added. Check the Category dropdown list.";
          touch( $dir_name . "/dummy.feature");
        }
      }
    }
  }

  if( $xaction == "add-user" && $_SESSION['role'] == 'admin' ) {
    if( !empty($_POST['userid']) && 
        !empty($_POST['password']) &&
        !empty($_POST['name']) &&  
        !empty($_POST['email']) &&
        $_POST['password'] == $_POST['password2']) {
    
      if( $user->create_user() === FALSE ) {
        $error_msg = "User exists! Try with a different UserID.";
        $xaction = 'create-user';
      } else { 
        $success_msg = "User created.";
      } 

    } else {
      $error_msg = "All the fields are not entered or passwords are not same.";
      $xaction = 'create-user';
    }
  }

  if( $xaction == "create-user" && $_SESSION['role'] == 'admin' ) {
    if( !empty($error_msg)) {
      echo "<div class=error>{$error_msg}</div>";
    }
?>
<div align=center>
  <link href="assets/style.css" rel="stylesheet" type="text/css">
<h1>Create VZBehat User</h1>
<div style="padding:20px; border:1px solid #777; background-color: #ddd; width:400px;" align=left>
	<form method=post id=create_user action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<p>
			Preferred User ID:<br><input class=behat-select-item type=text name=userid size=10 value="<?php echo @$_POST['userid']; ?>">
		</p><p>
			Your Password:<br><input class=behat-select-item type=password name=password size=20>
		</p><p>
			Re-enter Password:<br><input class=behat-select-item type=password name=password2 size=20>
		</p><p>
			Your Name:<br><input class=behat-select-item type=text name=name value="<?php echo @$_POST['name']; ?>" size=30>
		</p><p>
			Your Email:<br><input class=behat-select-item type=text name=email value="<?php echo @$_POST['email']; ?>" size=30>
		</p><p>
        <input type=hidden name=action value='add-user'>
        <input type=hidden name=token value="<?php echo $_SESSION['token']; ?>">
       <br><br>
       <a href="#" onClick="getElementById('create_user').submit();" class=behat-button>Create User</a>
       &nbsp;&nbsp;<a href="<?php echo $web_url; ?>" class=behat-button>Cancel</a>
	</form>
</div>
   </div>
<?php
    exit;
  }

  if( $xaction == "insert" ) {

    $f = $test_path . "/features/" . $xcategory . "/" . $xfilename . ".feature";

    if( preg_match( "/^[a-z0-9][a-z0-9_]+[a-z0-9]$/", trim($xfilename)) != 1 ) {
      $error_msg = "Feature file name is empty or contains unacceptable characters.";
    } else 
    if( $xfilename == "" ) {
      $error_msg = "Feature file name not provided. Cannot create new feature.";
    } else 
    if( file_exists( $f)) {
      $error_msg = "Feature file exists! Try a new name.";
    } else
    if( $xfeature_text == "" ) {
      $error_msg = "Feature is empty.";
    } else
    if( file_put_contents( $f, $xfeature_text, LOCK_EX) === FALSE ) {
      $error_msg = "Unable to create feature file. Check folder permission.";
    } else {
      $success_msg = "New feature created.";
      $xfeature_text = "";
      $xfilename = "";
    }
  }

  if( $xaction == "delete" && $_SESSION['role'] == 'admin' ) {

    if( !file_exists($xfeature_path)) {
      $error_msg = "Feature file not found.";
    } else
    if( unlink( $xfeature_path) === FALSE ) {
      $error_msg = "Unable to delete feature file. Check file permission.";
    } else {
      $success_msg = "Feature file deleted.";
    }
  }

  if( $xaction == "modify" ) {

    if( !file_exists( $xfeature_path )) {
      $error_msg = "Unable to locate feature file.";
    } else
    if( $xfeature_text == "" ) {
      $error_msg = "Feature is empty.";
    } else
    if( file_put_contents( $xfeature_path, $xfeature_text, LOCK_EX) === FALSE ) {
      $error_msg = "Unable to rewrite feature file. Check file permission.";
    } else {
      $success_msg = "Feature file modified.";
    }
  }

  if( $xaction == "edit-config" && $_SESSION['role'] == 'admin' ) {
    $xconfig_path = $test_path . "/behat.yml";
    $xconfig = file_get_contents( $xconfig_path);
    if( trim($xconfig) == "" ) {
      $error_msg = "Could not locate behat.yml. Please report to the application administrator.";
    }
  }

  if( $xaction == "view-config" ) {
    $xconfig_path = $test_path . "/behat.yml";
    $xconfig = file_get_contents( $xconfig_path);
    if( trim($xconfig) == "" ) {
      $error_msg = "Could not locate behat.yml. Please report to the application administrator.";
    }
  }

  if( $xaction == "save-config" && $_SESSION['role'] == 'admin' ) {
    $xconfig_path = $test_path . "/behat.yml";
    $xconfig = $_POST['config'];
    if( trim($xconfig) == "" ) {
      $error_msg = "Empty Behat configuration posted. Have you removed it by mistake? {$xconfig_path}";
    } else
    if( file_put_contents( $xconfig_path, $xconfig, LOCK_EX) === FALSE ) {
      $error_msg = "Could not write behat.yml. Check file permission. {$xconfig_path}";
    } else {
      $success_msg = "Behat configuration updated.";
    }
  }

  if( $xaction == "edit-context" && $_SESSION['role'] == 'admin' ) {
    $context_path = $test_path . "/features/bootstrap/VerizonContext.php";
    $xcontext = file_get_contents( $context_path);
    if( trim($xcontext) == "" ) {
      $error_msg = "Could not locate VerizonContext.php. Please report to the application administrator.";
    } 
  }

  if( $xaction == "view-context" ) {
    $context_path = $test_path . "/features/bootstrap/VerizonContext.php";
    $xcontext = file_get_contents( $context_path);
    if( trim($xcontext) == "" ) {
      $error_msg = "Could not locate VerizonContext.php. Please report to the application administrator.";
    } 
  }

  if( $xaction == "save-context" && $_SESSION['role'] == 'admin' ) {
    $xcontext_path = $test_path . "/features/bootstrap/VerizonContext.php";
    $xcontext = $_POST['context'];
    if( trim($xcontext) == "" ) {
      $error_msg = "Empty Verizon Context posted. Have you removed it by mistake? {$xcontext_path}";
    } else
    if( file_put_contents( $xcontext_path, $xcontext, LOCK_EX) === FALSE ) {
      $error_msg = "Could not write VerizonContext.php. Check file permission. {$xcontext_path}";
    } else {
      $success_msg = "Behat Verizon Context updated.";
    }
  }

  if( !empty($xapplication)) {
    $feature_folder = $test_path . "/features";
    $features = recursive_file_list( $feature_folder, "feature");

    foreach( $features as $feature ) {
      $feature_file = str_replace( "\\", "/", $feature);
      $feature_base  = @basename( $feature_file);
      $cat = @basename(dirname( $feature_file));
      $feature_categories[] = $cat;
      $feature_path_all[] = $feature_file;
    }

    $feature_categories = array_unique( $feature_categories, SORT_STRING);
    asort( $feature_categories);

    if( in_array( $xcategory, $feature_categories)) {
      for( $i = 0; $i < sizeof($feature_path_all); $i++ ) {
        $cat = @basename(dirname( $feature_path_all[$i]));
        if( $cat == $xcategory && basename($feature_path_all[$i]) != 'dummy.feature') {
//          echo $feature_path_all[$i] . "<br>";
          $feature_path[] = $feature_path_all[$i];
          $feature_title[] = basename($feature_path_all[$i]);
        }
      }
    }
  }
?>
<!DOCTYPE html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>Behat GUI</title>
  <script src="assets/jquery-1.9.0.min.js"></script>
  <link href="assets/style.css" rel="stylesheet" type="text/css">
</head>
<body>

<?php

  echo "<div  class=behat-select-group style='position:relative; top:0px; height:80px;'>\n";

  echo "  <div style='float:left; top:0px; left:0px;'>\n";
  echo "    <img src='assets/verizon.png' align=left>";
  echo "     <span class=behat-page-title>VZBehat Features - <span style='color:red;'>Preparation</span></span>";
  echo "  </div>\n";
  
  echo "  <div style='float:right; padding-right:10px;'>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;Category&nbsp;
         <select class=behat-select-item id=xcategory  onChange='self_post(\"category\");'>\n";
  echo "   <option class=behat-select-item value=''> Select Category </option>\n";

  foreach( $feature_categories as $fg) {
    echo "<option class=behat-select-item value='" . $fg . "' ";
    if( $fg == $xcategory ) {
      echo " SELECTED";
    }
    $dfg = $fg;
    if( $dfg == 'features' ) {
      $dfg = "(Uncategorized)";
    }
    echo ">" . ucwords(str_replace(array('_','-'), array(' ',' '), substr($dfg,0,30)))
           . "</option>\n";
  }
  echo "</select></div>\n";
  
  echo "  <div style='float:right;'>Application&nbsp;
            <select class=behat-select-item id=xapplication onChange='self_post(\"application\");'>\n";
  echo "      <option class=behat-select-item value=''> Select Application </option>\n";

  foreach( $apps as $a ) {

    if( in_array( $a, $applist) && is_dir($test_paths[$a])) {
      echo "          <option class=behat-select-item value='" . $a . "' ";
      if( $xapplication == $a ) {
        echo " SELECTED";
      }
      echo ">" . $a . "</option>\n";
    }
  }
  echo "</select></div>";
 
  $url = $web_url . "/logout.php";
  echo "<br><br><br>";
  echo "<span style='float:left; left:20px;'>Welcome <i>{$_SESSION['name']}</i> [{$_SESSION['role']}]</span>";
  echo "<span style='float:right; right:20px;'><span style='float:right;'><a class=menu-item href='" . $url . "'>Logout</a></span>";

  $url = $web_url . '/runbehat.php?a=' . $xapplication . '&c=' . $xcategory . '&t=' . $_SESSION['token'];
  echo "<span style='right:20px;'><span style='float:right;'><a class=menu-item href='" . $url . "'>Execute Tests</a></span>";

  echo "<span style='float:right;'><a class=menu-item href='#' onClick='self_post(\"refresh\");'>Edit Feature</a></span>";

  if( $_SESSION['role'] == 'admin' ) {
    echo "<span style=''><a class=menu-item href='#' onClick='self_post(\"create-user\");'>Create User</a></span>";
  }

  if( $xapplication != "" && $_SESSION['role'] == 'admin' ) {
    echo "<span style=''><a class=menu-item href='#' onClick='self_post(\"edit-context\");'>Edit Context</a></span>";
    echo "<span style=''><a class=menu-item href='#' onClick='self_post(\"edit-config\");'>Edit Config</a></span>";
  } else
  if( $xapplication != "" ) {
    echo "<span style=''><a class=menu-item href='#' onClick='self_post(\"view-context\");'>View Context</a></span>";
    echo "<span style=''><a class=menu-item href='#' onClick='self_post(\"view-config\");'>View Config</a></span>";
  }

  echo "</div>";

//  echo "</div></div>\n";
//  echo "<div style='border:1px dashed #ccc;'></div>";
  

  if( !empty($error_msg)) {
    echo "<div class=error>{$error_msg}</div>";
  } else
  if( !empty($success_msg)) {
    echo "<div class=success>{$success_msg}</div>";
  }

  echo "<div style='vertical-align:top; padding-top:50px; margin-left:120px;'>\n";

  if( $xapplication != "" ) {
    if( $xaction == "edit-context" ) {
      echo "<h3>Edit Verizon Context:</h3>";
      echo "<div style='margin-top:40px;'>\n";
      echo "VerizonContext.php:\n";
      echo "<br><textarea style='margin-top:10px;' id=xcontext rows=25 cols=100>\n";
      echo $xcontext;
      echo "</textarea>\n";
      echo "<br><br>&nbsp;&nbsp;<span class=behat-button><a href='#' onClick='self_post(\"save-context\");'>Save Context</a></span>";
    } else 
    if( $xaction == "edit-config" ) {
      echo "<h3>Edit Behat Configuration:</h3>";
      echo "<div style='margin-top:40px;'>\n";
      echo "behat.yml:\n";
      echo "<br><textarea style='margin-top:10px;' id=xconfig rows=25 cols=100>\n";
      echo $xconfig;
      echo "</textarea>\n";
      echo "<br><br>&nbsp;&nbsp;<span class=behat-button><a href='#' onClick='self_post(\"save-config\");'>Save Config</a></span>";
    } else 
    if( $xaction == "view-context" ) {
      echo "<h3>View Verizon Context:</h3>";
      echo "<div style='margin-top:40px;'>\n";
      echo "VerizonContext.php:\n";
      echo "<br><div class='code-text' style='display:block; margin-top:10px; width:800px; height:500px; overflow: auto;' id=xcontext>\n";
      echo  str_replace( array("\n", " "), array('<br>', '&nbsp;'), $xcontext);
      echo "</div>\n";
      echo "<br><br></span>";
    } else 
    if( $xaction == "view-config" ) {
      echo "<h3>View Behat Configuration:</h3>";
      echo "<div style='margin-top:40px;'>\n";
      echo "behat.yml:\n";
      echo "<br><div class=code-text style='display:block; margin-top:10px; width:800px; height:500px; overflow: auto;' id=xconfig>\n";
      echo str_replace( array("\n", " "), array('<br>', '&nbsp;'), $xconfig);
      echo "</div>\n";
      echo "<br><br>";
    } else {
      echo "<h3>Edit Features:</h3>";
      echo "<div>";
      echo "New Category <input class=behat-select-item type=text name=xnew_category id=xnew_category size=20 value='{$xnew_category}'>";
      echo "&nbsp;&nbsp;<span style='' class=behat-button><a href='#' onClick='self_post(\"add_category\");'>Add Category</a></span>";
      echo "<br><span style='margin-left:110px;' class=small>Characters allowed: [a-z], [0-9] &amp; _ (underscore).</span>";
      echo "</div>";
    }
  }

  if( $xapplication != "" && $xcategory != "" && $xaction != "edit-context" && $xaction != "edit-config" ) {
    echo "<div style='margin-top:40px;'>";
    echo "   <select style='vertical-align:top; margin-right:10px;' class=behat-select-item id=xfeature_path onChange='self_post(\"feature\");'>\n";
    echo "   <option class=behat-select-item value=''>Select Feature to Edit</option>\n";

    $feature_real_path = "";
    for( $i = 0; $i < sizeof($feature_title); $i++) {
      echo "<option class=behat-select-item value='" . $feature_path[$i] . "' ";
      if( $i == ($xfeature_index-1)) {
        echo " SELECTED";
        $feature_real_path = $feature_path[$i];
      }
      echo ">" . $feature_title[$i] . "</option>\n";
    }
    echo "</select>\n\n";

    echo "&nbsp;&nbsp;Feature File <input class=behat-select-item type=text name=xfilename id=xfilename size=50 value='{$xfilename}'>";
    echo "<br><span style='margin-left:330px;' class=small>Required only for NEW feature. Characters allowed: [a-z], [0-9] &amp; _ (underscore).</span>";
    echo "</div>\n";

    echo "<br><textarea style='margin-top:10px;' id=xfeature_text rows=20 cols=100>";
    if( trim($xfeature_text) != '' ) {
      echo $xfeature_text;
    } else 
    if( !empty( $feature_real_path)) {
      $f = @file_get_contents( $feature_real_path);
      echo $f;
    }
    echo "</textarea>\n";

    echo "<br><br>&nbsp;&nbsp;<span class=behat-button><a href='#' onClick='self_post(\"modify\");'>Modify Feature</a></span>";
    echo "&nbsp;&nbsp;<span style='' class=behat-button><a href='#' onClick='self_post(\"insert\");'>Insert Feature</a></span>";
	if( $_SESSION['role'] == 'admin' ) {
		echo "<span style='margin-left:380px;' class=behat-button><a href='#' onClick='self_post(\"delete\");'>Delete Feature</a></span>";
	}
    echo "   </div>";
  }

  echo "</div>\n\n";

  echo "<form id=form-data method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
  echo "   <input type=hidden name=application id=application value=''>\n";
  echo "   <input type=hidden name=category id=category value=''>\n";
  echo "   <input type=hidden name=new_category id=new_category value=''>\n";
  echo "   <input type=hidden name=feature_index id=feature_index value=''>\n";
  echo "   <input type=hidden name=feature_text id=feature_text value=''>\n";
  echo "   <input type=hidden name=feature_path id=feature_path value=''>\n";
  echo "   <input type=hidden name=filename id=filename value=''>\n";
  echo "   <input type=hidden name=context id=context value=''>\n";
  echo "   <input type=hidden name=config id=config value=''>\n";
  echo "   <input type=hidden name=action id=action value=''>\n";
  echo "   <input type=hidden name=token id=token value='".$_SESSION['token']."'>\n";
  echo "</form>\n\n";

  echo "<br><br>";
?>
<script language=javascript>
  function self_post( act) {
 
    $("#action").val(act);

    if( act == 'application' ) {
      $("#category").val( "");
      $("#feature_index").val( "0");
      $("#feature_path").val( "");
      $("#feature_text").val( "");
    }
    
    $("#application").val( $("#xapplication").val());

    if( act == 'category' ) {
      $("#feature_index").val( "0");
      $("#feature_path").val( "");
      $("#feature_text").val( "");
    }

    if( $("#xcategory").val() != "" ) {
      $("#category").val( $("#xcategory").val());
    }

    if( $("#application").val() != "" ) {
      if( act == 'view-context' ||  act == 'view-config' ) {
        $("#form-data").submit();
        return;
      } 
    }

    if( $("#application").val() != "" &&  $("#category").val() != "" ) {
      if( act == 'edit-context' ||  act == 'edit-config' ) {
        $("#form-data").submit();
        return;
      } 
    }

    if( act == 'save-context' ) {
      $("#context").val( $("#xcontext").val());
    }

    if( act == 'save-config' ) {
      $("#config").val( $("#xconfig").val());
    }

    if( act == 'add_category' ) {
      $("#new_category").val( $("#xnew_category").val());
      $("#feature_index").val( "0");
      $("#feature_path").val( "");
      $("#feature_text").val( "");
    }

    if( act != 'save-config' && act != 'save-context' ) {
      $("#feature_index").val( $("#xfeature_path option:selected").index());
    }

    if( act == 'feature' ) {
      $("#feature_path").val( $("#xfeature_path").val());
      $("#feature_text").val( "");
    }

    if( act == 'insert' ) {
      $("#feature_path").val( $("#xfeature_path").val());
      $("#feature_text").val( $("#xfeature_text").val());
      $("#filename").val( $("#xfilename").val());
      $("#feature_index").val(0);
    }

    if( act == 'modify' ) {
      $("#feature_path").val( $("#xfeature_path").val());
      $("#feature_text").val( $("#xfeature_text").val());
    }

    if( act == 'delete' ) {
      $("#feature_index").val( 0);
      $("#feature_path").val( $("#xfeature_path").val());
      $("#feature_text").val( "");
    }

    if( act == "modify" || act == "insert" || act == "delete" || act == "save-config" || act == "save-context" ) {
      if( confirm( "Do you want to proceed?")) {
        $("#form-data").submit();
      }
    } else {
      $("#form-data").submit();
    }
  }

$(document).ready(function(){
						   
 $(window).resize(function(){

  $('.center').css({
   position:'absolute',
   left: ($(window).width() 
     - $('.center').outerWidth())/2,
  });
		
 });
 
 // To initially run the function:
 $(window).resize();

});
</script>
</body>
</html>
