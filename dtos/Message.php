<?php
/**
 * 이 파일은 아이모듈 알림모듈의 일부입니다. (https://www.imodules.io)
 *
 * 알림메시지 구조체를 정의한다.
 *
 * @file /modules/push/dtos/Message.php
 * @author sungjin <esung246@naddle.net>
 * @license MIT License
 * @modified 2025. 3. 18.
 */
namespace modules\push\dtos;
class Message
{
    /**
     * @var ?string $_message_id 메시지고유값 (NULL 인 경우 아직 발송되지 않은 알림메시지)
     */
    private ?string $_message_id;

    /**
     * @var int $_member_id 수신자회원고유값
     */
    private int $_member_id;

    /**
     * @var string $_component_type 메시지를 전송한 컴포넌트종류
     */
    private string $_component_type;

    /**
     * @var string $_component_name 메시지를 전송한 컴포넌트명
     */
    private string $_component_name;

    /**
     * @var string $_target_type 알림대상종류
     */
    private string $_target_type;

    /**
     * @var mixed $_target_id 알림대상고유값
     */
    private mixed $_target_id;

    /**
     * @var string $_code 알림코드
     */
    private string $_code;

    /**
     * @var mixed[] $_contents 메시지내용
     */
    private array $_contents;

    /**
     * @var int $_sended_by 전송한회원고유값 (0 인 경우 시스템발송)
     */
    private int $_sended_by;

    /**
     * @var int $_sended_at 전송시각
     */
    private int $_sended_at;

    /**
     * @var bool $_is_checked 확인여부
     */
    private bool $_is_checked;

    /**
     * @var bool $_is_readed 읽음여부
     */
    private bool $_is_readed;

    /**
     * 알림메시지 구조체를 정의한다.
     *
     * @param object $message
     */
    public function __construct(object $message)
    {
        $this->_message_id = $message->message_id = null;
        $this->_member_id = $message->member_id;
        $this->_component_type = $message->component_type;
        $this->_component_name = $message->component_name;
        $this->_target_type = $message->target_type;
        $this->_target_id = $message->target_id;
        $this->_code = $message->code;
        $this->_contents = json_decode($message->contents);
        $this->_sended_by = $message->sended_by;
        $this->_sended_at = $message->sended_at;
        $this->_is_checked = $message->is_checked == 'TRUE';
        $this->_is_readed = $message->is_readed == 'TRUE';
    }

    /**
     * 메시지고유값을 가져온다.
     *
     * @return ?string $message_id
     */
    public function getId(): ?string
    {
        return $this->_message_id;
    }

    /**
     * 수신자 회원고유값을 가져온다.
     *
     * @return int $member_id
     */
    public function getMemberId(): int
    {
        return $this->_member_id;
    }

    /**
     * 알림을 전송한 컴포넌트를 가져온다.
     *
     * @return \Component $component
     */
    public function getComponent(): \Component
    {
        return \Component::get($this->_component_type, $this->_component_name);
    }

    /**
     * 알림대상종류를 가져온다.
     *
     * @return string $target_type
     */
    public function getTargetType(): string
    {
        return $this->_target_type;
    }

    /**
     * 알림대상고유값을 가져온다.
     *
     * @return mixed $target_id
     */
    public function getTargetId(): mixed
    {
        return $this->_target_id;
    }

    /**
     * 알림종류를 가져온다.
     *
     * @return string $code
     */
    public function getCode(): string
    {
        return $this->_code;
    }

    /**
     * 알림내용을 가져온다.
     *
     * @return mixed[] $contents
     */
    public function getContents(): array
    {
        return $this->_contents;
    }

    /**
     * 마지막 알림내용을 가져온다.
     *
     * @return mixed $content
     */
    public function getLatestContent(): mixed
    {
        return count($this->_contents) > 0 ? $this->_contents[count($this->_contents) - 1] : null;
    }

    /**
     * 알림을 전송한 회원정보를 가져온다.
     *
     * @return ?\modules\member\Member $sended_by (NULL 인 경우 시스템발송)
     */
    public function getSendedBy(): ?\modules\member\dtos\Member
    {
        if ($this->_sended_by == 0) {
            return null;
        }

        /**
         * @var \modules\member\Member $mMember
         */
        $mMember = \Modules::get('member');
        return $mMember->getMember($this->_sended_by);
    }

    /**
     * 전송시각을 가져온다.
     *
     * @return int $sended_at
     */
    public function getSendedAt(): int
    {
        return $this->_sended_at;
    }

    /**
     * 알림 확인여부를 가져온다.
     *
     * @return bool $is_checked
     */
    public function isChecked(): bool
    {
        return $this->_is_checked;
    }

    /**
     * 알림 읽음여부를 가져온다.
     *
     * @return bool $is_readed
     */
    public function isReaded(): bool
    {
        return $this->_is_readed;
    }
}
