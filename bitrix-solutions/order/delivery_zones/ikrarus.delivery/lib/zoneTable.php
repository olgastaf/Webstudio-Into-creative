<?php

namespace Ikrarus\Delivery;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

final class ZoneTable
{
    private const HIGHLOAD_BLOCK_ID = 4;

    private static ?string $dataClass = null;

    public static function getDataClass(): string
    {
        if (self::$dataClass !== null) {
            return self::$dataClass;
        }

        if (!Loader::includeModule('highloadblock')) {
            throw new \RuntimeException(
                'Не удалось подключить модуль highloadblock'
            );
        }

        $hlBlock = HighloadBlockTable::getById(
            self::HIGHLOAD_BLOCK_ID
        )->fetch();

        if (!is_array($hlBlock)) {
            throw new \RuntimeException(
                'HL-блок DeliveryZones с ID 4 не найден'
            );
        }

        $entity = HighloadBlockTable::compileEntity(
            $hlBlock
        );

        self::$dataClass = $entity->getDataClass();

        return self::$dataClass;
    }
}