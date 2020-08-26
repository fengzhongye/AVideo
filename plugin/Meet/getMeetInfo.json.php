<?php
header('Content-Type: application/json');
if (!isset($global['systemRootPath'])) {
    $configFile = '../../videos/configuration.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    }
}
$objM = AVideoPlugin::getObjectDataIfEnabled("Meet");
//_error_log(json_encode($_SERVER));
$obj = new stdClass();
$obj->error = true;
$obj->msg = "";
$obj->meet_schedule_id = 0;
$obj->html = "";

if (empty($objM)) {
    $obj->msg = "Plugin disabled";
    die(json_encode($obj));
}

$obj->meet_schedule_id = intval($_REQUEST['meet_schedule_id']);
if (empty($obj->meet_schedule_id)) {
    $obj->msg = "meet_schedule_id cannot be empty";
    die(json_encode($obj));
}

$ms = new Meet_schedule($obj->meet_schedule_id);

if (!$ms->canManageSchedule()) {
    $obj->msg = "You cant do this";
    die(json_encode($obj));
}

$obj->error = false;

ob_end_clean();
ob_start();


if ($_REQUEST['meet_scheduled'] !== "past") {
    $invitation = $objM->invitation->value;
    $topic = $ms->getTopic();
    $pass = $ms->getPassword();

    if (empty($topic)) {
        $invitation = preg_replace("/(\n|\r)[^\n\r]*{topic}[^\n\r]*(\n|\r)/i", "", $invitation);
    } else {
        $invitation = preg_replace("/{topic}/i", $topic, $invitation);
    }

    if (empty($pass)) {
        $invitation = preg_replace("/(\n|\r)[^\n\r]*{password}[^\n\r]*(\n|\r)/i", "", $invitation);
    } else {
        $invitation = preg_replace("/{password}/i", $pass, $invitation);
    }

    $invitation = preg_replace("/{UserName}/i", User::getNameIdentificationById($ms->getUsers_id()), $invitation);
    $invitation = preg_replace("/{meetLink}/i", $ms->getMeetLink(), $invitation);
    ?>
    <div class="row">
        <div class="form-group col-sm-9">
            <label for="RoomLink"><?php echo __("Meet Link"); ?>:</label>
            <?php
            getInputCopyToClipboard("RoomLink", $ms->getMeetLink());
            ?>
        </div>
        <div class="form-group col-sm-3">
            <label for="RoomInvitation"><?php echo __("Invitation"); ?>:</label>
            <textarea id="RoomInvitation" name="RoomInvitation" class="form-control input-sm hidden" placeholder="<?php echo __("Meet Invitation"); ?>" readonly><?php echo $invitation; ?></textarea>
            <?php
            echo getButtontCopyToClipboard("RoomInvitation", 'class="btn btn-default btn-block "', __("Copy"));
            ?>
        </div>
    </div>
    <?php
}
?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading"><?php echo __("Participants"); ?></div>
            <div class="panel-body">
                <ul class="list-group">
                    <?php
                    $count = 0;
                    $list = Meet_join_log::getAllFromSchedule($obj->meet_schedule_id);
                    foreach ($list as $value) {
                        $count++;
                        echo '<li class="list-group-item">#' . $count . " - " . User::getNameIdentificationById($value['users_id']) . ' <span class="badge">' . $value['created'] . '</span><br><small class="text-muted">' . $value['user_agent'] . '</small></li>';
                    }
                    if (empty($count)) {
                        echo '<li class="list-group-item">There are no participants on this Meet</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
if(!$ms->getPublic()){
?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading"><?php echo __("User Groups"); ?></div>
            <div class="panel-body">
                <ul class="list-group">
                    <?php
                    $count = 0;
                    $list = Meet_schedule_has_users_groups::getAllFromSchedule($obj->meet_schedule_id);
                    foreach ($list as $value) {
                        $count++;
                        echo '<li class="list-group-item">#' . $count . " - " . ($value['group_name']) . '</li>';
                    }
                    if (empty($count)) {
                        echo '<li class="list-group-item">There are no user groups selected for this Meet</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
}
$obj->html = ob_get_clean();
die(json_encode($obj));
?>