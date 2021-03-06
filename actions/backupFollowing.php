<?php

try {

    require __DIR__ . '/../config.php';

    $userNameSearched = getenv('USERNAME_SEARCHED');

    if(is_null($userNameSearched) || empty($userNameSearched)) {
        throw new Exception("You need to define `USERNAME_SEARCHED` in .env file");
    }

    $backupDataFolder = getenv('BACKUP_DATA_FOLDER');
    if(is_null($backupDataFolder) || empty($backupDataFolder)) {
        throw new Exception("You need to define `BACKUP_DATA_FOLDER` in .env file");
    }

    $info = $instagramAPI->people->getInfoByName($userNameSearched);
    file_put_contents("{$backupDataFolder}/{$userNameSearched}_infos.json", json_encode($info));
    file_put_contents("{$backupDataFolder}/{$userNameSearched}_infos.txt", print_r($info, true));

    $following = [
        'users' => [],
        'count' => 0
    ];

    $maxId = null;
    $rankToken = \InstagramAPI\Signatures::generateUUID();
    $searchQuery = null;

    do {
        $response = $instagramAPI->people->getFollowing(
            $info->getUser()->getPk(),
            $rankToken,
            $searchQuery,
            $maxId
        );
        $apiUsers = $response->getUsers();
        foreach ($apiUsers as $key => $userModel) {
            /**
             * @var $userModel \InstagramAPI\Response\Model\User
             */
            end($following['users']);
            $userIndex = (int)key($following['users']) + 1;
            reset($following['users']);

            $following['users'][$userIndex]['pk'] = $userModel->getPk();
            $following['users'][$userIndex]['username'] = $userModel->getUsername();
            $following['users'][$userIndex]['full_name'] = $userModel->getFullName();
        }
        $following['count'] += count($apiUsers);
        $following['nextMaxId'] = $response->getNextMaxId();
        $maxId = $following['nextMaxId'];
    } while ($maxId !== null);

    file_put_contents("{$backupDataFolder}/{$userNameSearched}_following.json", json_encode($following));
    file_put_contents("{$backupDataFolder}/{$userNameSearched}_following.txt", print_r($following, true));

    $instagramAPI->logout();

    print "\n=== [ Backup of Following - Complete! ] ===\n";

} catch (\Exception $e) {
    echo 'Something went wrong: ' . $e->getMessage() . "\n";
}

