<?php
/**
 * 이 파일은 아이모듈 알림모듈의 일부입니다. (https://www.imodules.io)
 *
 * 알림메시지를 가져온다.
 *
 * @file /modules/push/processes/messages.get.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 10.
 *
 * @var \modules\push\Push $me
 */
if (defined('__IM_PROCESS__') == false) {
    exit();
}

$me->getSender(Modules::get('naddle/desk'))
    ->setTo(0, '장진우', 'arzz@arzz.com', '010-3237-0270')
    ->setTarget('issue', '0336fdc0-8454-11ef-867f-20756cd6876f')
    ->setContent('status', ['status' => 'ACCEPT'])
    ->send();
$results->success = true;
