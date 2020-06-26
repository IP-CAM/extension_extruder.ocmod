<?php
$this->load->model("user/user_group");
$this->model_user_user_group->addPermission($this->user->getGroupId(), "access", "extension/extension_extruder");
$this->model_user_user_group->addPermission($this->user->getGroupId(), "modify", "extension/extension_extruder");