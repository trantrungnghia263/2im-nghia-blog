<?php
/**
 * Created by Another user.
 * User: ???
 * Date: 12/13/2017
 * Time: 3:27 PM
 */
require_once '../curl.php';
function getFs_csrf($page){
    preg_match('#<meta name="csrf-token" content="(.*?)">#',$page,$get_fs_csrf);
    $fs_csrf = $get_fs_csrf[1];
    return $fs_csrf;
}

function getIdFshareUrl($url){
    preg_match('/https:\/\/www\.fshare\.vn\/file\/(.[a-zA-Z0-9]+)?/', $url, $idFile);
    $id = $idFile[1];
    return $id;
}

function login($fs_csrf){
    $acc_user = 'vuhoang666888@gmail.com';
    global $curl;
    $curl = new cURL();
    $acc_pass = 'Ho@ng666888';
    $login_url = "https://www.fshare.vn/site/login";
    $dataLogin = "_csrf-app=" .  urlencode($fs_csrf) . "&LoginForm%5Bemail%5D=" . urlencode($acc_user) . "&LoginForm%5Bpassword%5D=" . urlencode($acc_pass) . "&LoginForm%5BrememberMe%5D=0";
    $html = $curl->post($login_url,$dataLogin);;
}

function getLink($fs_csrf, $fslink){
    global $curl;
    $idURL = getIdFshareUrl($fslink);
    $dataDownload= '_csrf-app=' . urlencode($fs_csrf) . '&linkcode='.$idURL.'&withFcode5=0&fcode5=';
    $getLinkDownload = $curl->post('https://www.fshare.vn/download/get', $dataDownload);
    if(strpos($getLinkDownload, '{"url"')){
        $linkDownload = substr($getLinkDownload, strpos($getLinkDownload, '{'));
        $arrLinkDownload = json_decode($linkDownload);
        $url = $arrLinkDownload->url;
        $nameFile = substr($url, strrpos($url, '/')+1);
        $nameFile = urldecode($nameFile);
        $result = array(
            'url' => $url,
            'name' => $nameFile
        );
        return $result;
    }else{
        return false;
    }
}
    $fslink = $_POST['link'];
    $curl = new cURL();
    $page = $curl->get($fslink);
    $fs_csrf = getFs_csrf($page);
    login($fs_csrf);
    $file = getlink($fs_csrf,$fslink);
    echo $file['url'];
?>