<?php

namespace App\Entity\Enum;

/**
 * Codes ISO 3166-1 alpha-2 des 54 pays de la Zone Afrique (membres de l'Union africaine).
 */
enum AfricanCountry: string
{
    case Algeria = 'DZ';
    case Angola = 'AO';
    case Benin = 'BJ';
    case Botswana = 'BW';
    case BurkinaFaso = 'BF';
    case Burundi = 'BI';
    case CaboVerde = 'CV';
    case Cameroon = 'CM';
    case CentralAfricanRepublic = 'CF';
    case Chad = 'TD';
    case Comoros = 'KM';
    case CongoBrazzaville = 'CG';
    case CongoKinshasa = 'CD';
    case CoteDIvoire = 'CI';
    case Djibouti = 'DJ';
    case Egypt = 'EG';
    case EquatorialGuinea = 'GQ';
    case Eritrea = 'ER';
    case Eswatini = 'SZ';
    case Ethiopia = 'ET';
    case Gabon = 'GA';
    case Gambia = 'GM';
    case Ghana = 'GH';
    case Guinea = 'GN';
    case GuineaBissau = 'GW';
    case Kenya = 'KE';
    case Lesotho = 'LS';
    case Liberia = 'LR';
    case Libya = 'LY';
    case Madagascar = 'MG';
    case Malawi = 'MW';
    case Mali = 'ML';
    case Mauritania = 'MR';
    case Mauritius = 'MU';
    case Morocco = 'MA';
    case Mozambique = 'MZ';
    case Namibia = 'NA';
    case Niger = 'NE';
    case Nigeria = 'NG';
    case Rwanda = 'RW';
    case SaoTomeAndPrincipe = 'ST';
    case Senegal = 'SN';
    case Seychelles = 'SC';
    case SierraLeone = 'SL';
    case Somalia = 'SO';
    case SouthAfrica = 'ZA';
    case SouthSudan = 'SS';
    case Sudan = 'SD';
    case Tanzania = 'TZ';
    case Togo = 'TG';
    case Tunisia = 'TN';
    case Uganda = 'UG';
    case Zambia = 'ZM';
    case Zimbabwe = 'ZW';

    /**
     * @return string[]
     */
    public static function codes(): array
    {
        return array_map(static fn (self $country) => $country->value, self::cases());
    }
}
