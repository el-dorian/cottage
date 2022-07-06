<?php


namespace app\models\selections;


use JetBrains\PhpStorm\ArrayShape;

class AjaxRequestStatus
{
    private const STATUS_SUCCESS = 1;
    private const STATUS_FAILED = 0;

    #[ArrayShape(['status' => "int"])] public static function success(): array
    {
        return ['status' => self::STATUS_SUCCESS];
    }

    #[ArrayShape(['status' => "int", 'message' => "string"])] public static function failed(string $message): array
    {
        return ['status' => self::STATUS_FAILED, 'message' => $message];
    }

    #[ArrayShape(['status' => "int", 'title' => "string", 'view' => "string", 'delay' => "false"])] public static function view(string $title, string $view): array
    {
        return ['status' => self::STATUS_SUCCESS, 'title' => $title, 'view' => $view, 'delay' => false];
    }

    #[ArrayShape(['status' => "int", 'message' => "string", 'reload' => "bool"])] public static function successAndReload(string $message): array
    {
        return ['status' => self::STATUS_SUCCESS, 'message' => $message, 'reload' => true];
    }

    public static function successWithMessage($message)
    {
        return ['status' => self::STATUS_SUCCESS, 'message' => $message];
    }
}