<?php

namespace Config;

final class Disposition
{
    public const MAX_STEPS = 5;

    public const AGENDARIS_EDITABLE_STEPS = 3;

    public const RECIPIENTS = [
        'M. Robith Azmi',
        'Antoro Asih',
        'Aneka Prasyanti W.S',
        'Hilmiyah Fitriana',
        'DeniTri Kuncoro',
        'Dhia Shofiah Mardiana',
        'Faridatul Mukhodaroh',
        'Muhammad Khoirudin',
        'Alfan Andrianto',
        'Indro Hartanto',
        'Najmah Arofah',
        'Ridhotul Khafshoh Islami',
        'Kiki Ramadhani Suyono',
        'Angger Wicaksono',
        'Tri Novita Sari',
        'Agil Halis Kesawa',
    ];

    /** @return list<string> */
    public static function atkPickupRecipients(): array
    {
        return [
            ...self::RECIPIENTS,
            'Kemal Rismandaru',
            'Riyan Pradana',
            'Gandes Novia Wulansari',
            'Irgun Wahju Drijanto',
            'Joni Slamet Edi S',
        ];
    }

    public const STATUSES = ['Menunggu', 'Diterima', 'Diproses', 'Diteruskan', 'Selesai'];

    public const RETURN_TO_AGENDARIS_STATUS = 'Kembalikan ke Agendaris';

    /** @return list<string> */
    public static function sdmStatusOptions(): array
    {
        return [...self::STATUSES, self::RETURN_TO_AGENDARIS_STATUS];
    }
}
