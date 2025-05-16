<?php
//StoreProfile is extendin user profile because at many places current logged in user
//is of type UserProfile, which actually is a store
include_once('model_extensions/class.LoggableUserModelExtension.php');
//included cheetah store profile
include_once('helper/StoreProfile.php');
?>
