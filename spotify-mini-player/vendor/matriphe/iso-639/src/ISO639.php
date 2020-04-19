<?php

namespace Matriphe\ISO639;

class ISO639
{
    /*
     * Language database, based on Wikipedia.
     * Source: https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     */
    protected $languages = array(
        array('ab', 'abk', 'abk', 'abk', 'Abkhaz', 'аҧсуа бызшәа, аҧсшәа'),
        array('aa', 'aar', 'aar', 'aar', 'Afar', 'Afaraf'),
        array('af', 'afr', 'afr', 'afr', 'Afrikaans', 'Afrikaans'),
        array('ak', 'aka', 'aka', 'aka', 'Akan', 'Akan'),
        array('sq', 'sqi', 'alb', 'sqi', 'Albanian', 'Shqip'),
        array('am', 'amh', 'amh', 'amh', 'Amharic', 'አማርኛ'),
        array('ar', 'ara', 'ara', 'ara', 'Arabic', 'العربية'),
        array('an', 'arg', 'arg', 'arg', 'Aragonese', 'aragonés'),
        array('hy', 'hye', 'arm', 'hye', 'Armenian', 'Հայերեն'),
        array('as', 'asm', 'asm', 'asm', 'Assamese', 'অসমীয়া'),
        array('av', 'ava', 'ava', 'ava', 'Avaric', 'авар мацӀ, магӀарул мацӀ'),
        array('ae', 'ave', 'ave', 'ave', 'Avestan', 'avesta'),
        array('ay', 'aym', 'aym', 'aym', 'Aymara', 'aymar aru'),
        array('az', 'aze', 'aze', 'aze', 'Azerbaijani', 'azərbaycan dili'),
        array('bm', 'bam', 'bam', 'bam', 'Bambara', 'bamanankan'),
        array('ba', 'bak', 'bak', 'bak', 'Bashkir', 'башҡорт теле'),
        array('eu', 'eus', 'baq', 'eus', 'Basque', 'euskara, euskera'),
        array('be', 'bel', 'bel', 'bel', 'Belarusian', 'беларуская мова'),
        array('bn', 'ben', 'ben', 'ben', 'Bengali, Bangla', 'বাংলা'),
        array('bh', 'bih', 'bih', '', 'Bihari', 'भोजपुरी'),
        array('bi', 'bis', 'bis', 'bis', 'Bislama', 'Bislama'),
        array('bs', 'bos', 'bos', 'bos', 'Bosnian', 'bosanski jezik'),
        array('br', 'bre', 'bre', 'bre', 'Breton', 'brezhoneg'),
        array('bg', 'bul', 'bul', 'bul', 'Bulgarian', 'български език'),
        array('my', 'mya', 'bur', 'mya', 'Burmese', 'ဗမာစာ'),
        array('ca', 'cat', 'cat', 'cat', 'Catalan', 'català'),
        array('ch', 'cha', 'cha', 'cha', 'Chamorro', 'Chamoru'),
        array('ce', 'che', 'che', 'che', 'Chechen', 'нохчийн мотт'),
        array('ny', 'nya', 'nya', 'nya', 'Chichewa, Chewa, Nyanja', 'chiCheŵa, chinyanja'),
        array('zh', 'zho', 'chi', 'zho', 'Chinese', '中文 (Zhōngwén), 汉语, 漢語'),
        array('cv', 'chv', 'chv', 'chv', 'Chuvash', 'чӑваш чӗлхи'),
        array('kw', 'cor', 'cor', 'cor', 'Cornish', 'Kernewek'),
        array('co', 'cos', 'cos', 'cos', 'Corsican', 'corsu, lingua corsa'),
        array('cr', 'cre', 'cre', 'cre', 'Cree', 'ᓀᐦᐃᔭᐍᐏᐣ'),
        array('hr', 'hrv', 'hrv', 'hrv', 'Croatian', 'hrvatski jezik'),
        array('cs', 'ces', 'cze', 'ces', 'Czech', 'čeština, český jazyk'),
        array('da', 'dan', 'dan', 'dan', 'Danish', 'dansk'),
        array('dv', 'div', 'div', 'div', 'Divehi, Dhivehi, Maldivian', 'ދިވެހި'),
        array('nl', 'nld', 'dut', 'nld', 'Dutch', 'Nederlands, Vlaams'),
        array('dz', 'dzo', 'dzo', 'dzo', 'Dzongkha', 'རྫོང་ཁ'),
        array('en', 'eng', 'eng', 'eng', 'English', 'English'),
        array('eo', 'epo', 'epo', 'epo', 'Esperanto', 'Esperanto'),
        array('et', 'est', 'est', 'est', 'Estonian', 'eesti, eesti keel'),
        array('ee', 'ewe', 'ewe', 'ewe', 'Ewe', 'Eʋegbe'),
        array('fo', 'fao', 'fao', 'fao', 'Faroese', 'føroyskt'),
        array('fj', 'fij', 'fij', 'fij', 'Fijian', 'vosa Vakaviti'),
        array('fi', 'fin', 'fin', 'fin', 'Finnish', 'suomi, suomen kieli'),
        array('fr', 'fra', 'fre', 'fra', 'French', 'français, langue française'),
        array('ff', 'ful', 'ful', 'ful', 'Fula, Fulah, Pulaar, Pular', 'Fulfulde, Pulaar, Pular'),
        array('gl', 'glg', 'glg', 'glg', 'Galician', 'galego'),
        array('ka', 'kat', 'geo', 'kat', 'Georgian', 'ქართული'),
        array('de', 'deu', 'ger', 'deu', 'German', 'Deutsch'),
        array('el', 'ell', 'gre', 'ell', 'Greek (modern)', 'ελληνικά'),
        array('gn', 'grn', 'grn', 'grn', 'Guaraní', 'Avañe\'ẽ'),
        array('gu', 'guj', 'guj', 'guj', 'Gujarati', 'ગુજરાતી'),
        array('ht', 'hat', 'hat', 'hat', 'Haitian, Haitian Creole', 'Kreyòl ayisyen'),
        array('ha', 'hau', 'hau', 'hau', 'Hausa', '(Hausa) هَوُسَ'),
        array('he', 'heb', 'heb', 'heb', 'Hebrew (modern)', 'עברית'),
        array('hz', 'her', 'her', 'her', 'Herero', 'Otjiherero'),
        array('hi', 'hin', 'hin', 'hin', 'Hindi', 'हिन्दी, हिंदी'),
        array('ho', 'hmo', 'hmo', 'hmo', 'Hiri Motu', 'Hiri Motu'),
        array('hu', 'hun', 'hun', 'hun', 'Hungarian', 'magyar'),
        array('ia', 'ina', 'ina', 'ina', 'Interlingua', 'Interlingua'),
        array('id', 'ind', 'ind', 'ind', 'Indonesian', 'Bahasa Indonesia'),
        array('ie', 'ile', 'ile', 'ile', 'Interlingue', 'Originally called Occidental; then Interlingue after WWII'),
        array('ga', 'gle', 'gle', 'gle', 'Irish', 'Gaeilge'),
        array('ig', 'ibo', 'ibo', 'ibo', 'Igbo', 'Asụsụ Igbo'),
        array('ik', 'ipk', 'ipk', 'ipk', 'Inupiaq', 'Iñupiaq, Iñupiatun'),
        array('io', 'ido', 'ido', 'ido', 'Ido', 'Ido'),
        array('is', 'isl', 'ice', 'isl', 'Icelandic', 'Íslenska'),
        array('it', 'ita', 'ita', 'ita', 'Italian', 'italiano'),
        array('iu', 'iku', 'iku', 'iku', 'Inuktitut', 'ᐃᓄᒃᑎᑐᑦ'),
        array('ja', 'jpn', 'jpn', 'jpn', 'Japanese', '日本語 (にほんご)'),
        array('jv', 'jav', 'jav', 'jav', 'Javanese', 'basa Jawa'),
        array('kl', 'kal', 'kal', 'kal', 'Kalaallisut, Greenlandic', 'kalaallisut, kalaallit oqaasii'),
        array('kn', 'kan', 'kan', 'kan', 'Kannada', 'ಕನ್ನಡ'),
        array('kr', 'kau', 'kau', 'kau', 'Kanuri', 'Kanuri'),
        array('ks', 'kas', 'kas', 'kas', 'Kashmiri', 'कश्मीरी, كشميري‎'),
        array('kk', 'kaz', 'kaz', 'kaz', 'Kazakh', 'қазақ тілі'),
        array('km', 'khm', 'khm', 'khm', 'Khmer', 'ខ្មែរ, ខេមរភាសា, ភាសាខ្មែរ'),
        array('ki', 'kik', 'kik', 'kik', 'Kikuyu, Gikuyu', 'Gĩkũyũ'),
        array('rw', 'kin', 'kin', 'kin', 'Kinyarwanda', 'Ikinyarwanda'),
        array('ky', 'kir', 'kir', 'kir', 'Kyrgyz', 'Кыргызча, Кыргыз тили'),
        array('kv', 'kom', 'kom', 'kom', 'Komi', 'коми кыв'),
        array('kg', 'kon', 'kon', 'kon', 'Kongo', 'Kikongo'),
        array('ko', 'kor', 'kor', 'kor', 'Korean', '한국어, 조선어'),
        array('ku', 'kur', 'kur', 'kur', 'Kurdish', 'Kurdî, كوردی‎'),
        array('kj', 'kua', 'kua', 'kua', 'Kwanyama, Kuanyama', 'Kuanyama'),
        array('la', 'lat', 'lat', 'lat', 'Latin', 'latine, lingua latina'),
        array('', '', '', 'lld', 'Ladin', 'ladin, lingua ladina'),
        array('lb', 'ltz', 'ltz', 'ltz', 'Luxembourgish, Letzeburgesch', 'Lëtzebuergesch'),
        array('lg', 'lug', 'lug', 'lug', 'Ganda', 'Luganda'),
        array('li', 'lim', 'lim', 'lim', 'Limburgish, Limburgan, Limburger', 'Limburgs'),
        array('ln', 'lin', 'lin', 'lin', 'Lingala', 'Lingála'),
        array('lo', 'lao', 'lao', 'lao', 'Lao', 'ພາສາລາວ'),
        array('lt', 'lit', 'lit', 'lit', 'Lithuanian', 'lietuvių kalba'),
        array('lu', 'lub', 'lub', 'lub', 'Luba-Katanga', 'Tshiluba'),
        array('lv', 'lav', 'lav', 'lav', 'Latvian', 'latviešu valoda'),
        array('gv', 'glv', 'glv', 'glv', 'Manx', 'Gaelg, Gailck'),
        array('mk', 'mkd', 'mac', 'mkd', 'Macedonian', 'македонски јазик'),
        array('mg', 'mlg', 'mlg', 'mlg', 'Malagasy', 'fiteny malagasy'),
        array('ms', 'msa', 'may', 'msa', 'Malay', 'bahasa Melayu, بهاس ملايو‎'),
        array('ml', 'mal', 'mal', 'mal', 'Malayalam', 'മലയാളം'),
        array('mt', 'mlt', 'mlt', 'mlt', 'Maltese', 'Malti'),
        array('mi', 'mri', 'mao', 'mri', 'Māori', 'te reo Māori'),
        array('mr', 'mar', 'mar', 'mar', 'Marathi (Marāṭhī)', 'मराठी'),
        array('mh', 'mah', 'mah', 'mah', 'Marshallese', 'Kajin M̧ajeļ'),
        array('mn', 'mon', 'mon', 'mon', 'Mongolian', 'монгол'),
        array('na', 'nau', 'nau', 'nau', 'Nauru', 'Ekakairũ Naoero'),
        array('nv', 'nav', 'nav', 'nav', 'Navajo, Navaho', 'Diné bizaad'),
        array('nd', 'nde', 'nde', 'nde', 'Northern Ndebele', 'isiNdebele'),
        array('ne', 'nep', 'nep', 'nep', 'Nepali', 'नेपाली'),
        array('ng', 'ndo', 'ndo', 'ndo', 'Ndonga', 'Owambo'),
        array('nb', 'nob', 'nob', 'nob', 'Norwegian Bokmål', 'Norsk bokmål'),
        array('nn', 'nno', 'nno', 'nno', 'Norwegian Nynorsk', 'Norsk nynorsk'),
        array('no', 'nor', 'nor', 'nor', 'Norwegian', 'Norsk'),
        array('ii', 'iii', 'iii', 'iii', 'Nuosu', 'ꆈꌠ꒿ Nuosuhxop'),
        array('nr', 'nbl', 'nbl', 'nbl', 'Southern Ndebele', 'isiNdebele'),
        array('oc', 'oci', 'oci', 'oci', 'Occitan', 'occitan, lenga d\'òc'),
        array('oj', 'oji', 'oji', 'oji', 'Ojibwe, Ojibwa', 'ᐊᓂᔑᓈᐯᒧᐎᓐ'),
        array('cu', 'chu', 'chu', 'chu', 'Old Church Slavonic, Church Slavonic, Old Bulgarian', 'ѩзыкъ словѣньскъ'),
        array('om', 'orm', 'orm', 'orm', 'Oromo', 'Afaan Oromoo'),
        array('or', 'ori', 'ori', 'ori', 'Oriya', 'ଓଡ଼ିଆ'),
        array('os', 'oss', 'oss', 'oss', 'Ossetian, Ossetic', 'ирон æвзаг'),
        array('pa', 'pan', 'pan', 'pan', 'Panjabi, Punjabi', 'ਪੰਜਾਬੀ, پنجابی‎'),
        array('pi', 'pli', 'pli', 'pli', 'Pāli', 'पाऴि'),
        array('fa', 'fas', 'per', 'fas', 'Persian (Farsi)', 'فارسی'),
        array('pl', 'pol', 'pol', 'pol', 'Polish', 'język polski, polszczyzna'),
        array('ps', 'pus', 'pus', 'pus', 'Pashto, Pushto', 'پښتو'),
        array('pt', 'por', 'por', 'por', 'Portuguese', 'português'),
        array('qu', 'que', 'que', 'que', 'Quechua', 'Runa Simi, Kichwa'),
        array('rm', 'roh', 'roh', 'roh', 'Romansh', 'rumantsch grischun'),
        array('rn', 'run', 'run', 'run', 'Kirundi', 'Ikirundi'),
        array('ro', 'ron', 'rum', 'ron', 'Romanian', 'limba română'),
        array('ru', 'rus', 'rus', 'rus', 'Russian', 'Русский'),
        array('sa', 'san', 'san', 'san', 'Sanskrit (Saṁskṛta)', 'संस्कृतम्'),
        array('sc', 'srd', 'srd', 'srd', 'Sardinian', 'sardu'),
        array('sd', 'snd', 'snd', 'snd', 'Sindhi', 'सिन्धी, سنڌي، سندھی‎'),
        array('se', 'sme', 'sme', 'sme', 'Northern Sami', 'Davvisámegiella'),
        array('sm', 'smo', 'smo', 'smo', 'Samoan', 'gagana fa\'a Samoa'),
        array('sg', 'sag', 'sag', 'sag', 'Sango', 'yângâ tî sängö'),
        array('sr', 'srp', 'srp', 'srp', 'Serbian', 'српски језик'),
        array('gd', 'gla', 'gla', 'gla', 'Scottish Gaelic, Gaelic', 'Gàidhlig'),
        array('sn', 'sna', 'sna', 'sna', 'Shona', 'chiShona'),
        array('si', 'sin', 'sin', 'sin', 'Sinhala, Sinhalese', 'සිංහල'),
        array('sk', 'slk', 'slo', 'slk', 'Slovak', 'slovenčina, slovenský jazyk'),
        array('sl', 'slv', 'slv', 'slv', 'Slovene', 'slovenski jezik, slovenščina'),
        array('so', 'som', 'som', 'som', 'Somali', 'Soomaaliga, af Soomaali'),
        array('st', 'sot', 'sot', 'sot', 'Southern Sotho', 'Sesotho'),
        array('es', 'spa', 'spa', 'spa', 'Spanish', 'español'),
        array('su', 'sun', 'sun', 'sun', 'Sundanese', 'Basa Sunda'),
        array('sw', 'swa', 'swa', 'swa', 'Swahili', 'Kiswahili'),
        array('ss', 'ssw', 'ssw', 'ssw', 'Swati', 'SiSwati'),
        array('sv', 'swe', 'swe', 'swe', 'Swedish', 'svenska'),
        array('ta', 'tam', 'tam', 'tam', 'Tamil', 'தமிழ்'),
        array('te', 'tel', 'tel', 'tel', 'Telugu', 'తెలుగు'),
        array('tg', 'tgk', 'tgk', 'tgk', 'Tajik', 'тоҷикӣ, toçikī, تاجیکی‎'),
        array('th', 'tha', 'tha', 'tha', 'Thai', 'ไทย'),
        array('ti', 'tir', 'tir', 'tir', 'Tigrinya', 'ትግርኛ'),
        array('bo', 'bod', 'tib', 'bod', 'Tibetan Standard, Tibetan, Central', 'བོད་ཡིག'),
        array('tk', 'tuk', 'tuk', 'tuk', 'Turkmen', 'Türkmen, Түркмен'),
        array('tl', 'tgl', 'tgl', 'tgl', 'Tagalog', 'Wikang Tagalog, ᜏᜒᜃᜅ᜔ ᜆᜄᜎᜓᜄ᜔'),
        array('tn', 'tsn', 'tsn', 'tsn', 'Tswana', 'Setswana'),
        array('to', 'ton', 'ton', 'ton', 'Tonga (Tonga Islands)', 'faka Tonga'),
        array('tr', 'tur', 'tur', 'tur', 'Turkish', 'Türkçe'),
        array('ts', 'tso', 'tso', 'tso', 'Tsonga', 'Xitsonga'),
        array('tt', 'tat', 'tat', 'tat', 'Tatar', 'татар теле, tatar tele'),
        array('tw', 'twi', 'twi', 'twi', 'Twi', 'Twi'),
        array('ty', 'tah', 'tah', 'tah', 'Tahitian', 'Reo Tahiti'),
        array('ug', 'uig', 'uig', 'uig', 'Uyghur', 'ئۇيغۇرچە‎, Uyghurche'),
        array('uk', 'ukr', 'ukr', 'ukr', 'Ukrainian', 'українська мова'),
        array('ur', 'urd', 'urd', 'urd', 'Urdu', 'اردو'),
        array('uz', 'uzb', 'uzb', 'uzb', 'Uzbek', 'Oʻzbek, Ўзбек, أۇزبېك‎'),
        array('ve', 'ven', 'ven', 'ven', 'Venda', 'Tshivenḓa'),
        array('vi', 'vie', 'vie', 'vie', 'Vietnamese', 'Việt Nam'),
        array('vo', 'vol', 'vol', 'vol', 'Volapük', 'Volapük'),
        array('wa', 'wln', 'wln', 'wln', 'Walloon', 'walon'),
        array('cy', 'cym', 'wel', 'cym', 'Welsh', 'Cymraeg'),
        array('wo', 'wol', 'wol', 'wol', 'Wolof', 'Wollof'),
        array('fy', 'fry', 'fry', 'fry', 'Western Frisian', 'Frysk'),
        array('xh', 'xho', 'xho', 'xho', 'Xhosa', 'isiXhosa'),
        array('yi', 'yid', 'yid', 'yid', 'Yiddish', 'ייִדיש'),
        array('yo', 'yor', 'yor', 'yor', 'Yoruba', 'Yorùbá'),
        array('za', 'zha', 'zha', 'zha', 'Zhuang, Chuang', 'Saɯ cueŋƅ, Saw cuengh'),
        array('zu', 'zul', 'zul', 'zul', 'Zulu', 'isiZulu'),
    );

    /*
     * Get all language data
     *
     * @return (array)
    */
    public function allLanguages()
    {
        return $this->languages;
    }

    /*
     * Get language name from ISO-639-1 (two-letters code)
     *
     * @return (string)
     */
    public function languageByCode1($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[0] == $code) {
                $result = $lang[4];
                break;
            }
        }

        return $result;
    }

    /*
     * Get native language name from ISO-639-1 (two-letters code)
     *
     * @return (string)
     */
    public function nativeByCode1($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[0] == $code) {
                $result = $lang[5];
                break;
            }
        }

        return $result;
    }

    /*
     * Get language name from ISO-639-2/t (three-letter codes) terminologic
     *
     * @return (string)
     */
    public function languageByCode2t($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[1] == $code) {
                $result = $lang[4];
                break;
            }
        }

        return $result;
    }

    /*
     * Get native language name from ISO-639-2/t (three-letter codes) terminologic
     *
     * @return (string)
     */
    public function nativeByCode2t($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[1] == $code) {
                $result = $lang[5];
                break;
            }
        }

        return $result;
    }

    /*
     * Get language name from ISO-639-2/b (three-letter codes) bibliographic
     *
     * @return (string)
     */
    public function languageByCode2b($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[2] == $code) {
                $result = $lang[4];
                break;
            }
        }

        return $result;
    }

    /*
     * Get native language name from ISO-639-2/b (three-letter codes) bibliographic
     *
     * @return (string)
     */
    public function nativeByCode2b($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[2] == $code) {
                $result = $lang[5];
                break;
            }
        }

        return $result;
    }

    /*
     * Get language name from ISO-639-3 (three-letter codes)
     *
     * @return (string)
     */
    public function languageByCode3($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[3] == $code) {
                $result = $lang[4];
                break;
            }
        }

        return $result;
    }

    /*
     * Get native language name from ISO-639-3 (three-letter codes)
     *
     * @return (string)
     */
    public function nativeByCode3($code)
    {
        $code = strtolower($code);

        $result = '';

        foreach ($this->languages as $lang) {
            if ($lang[3] == $code) {
                $result = $lang[5];
                break;
            }
        }

        return $result;
    }

    /*
     * Get ISO-639-1 (two-letters code) from language name
     *
     * @return (string)
     */
    public function code1ByLanguage($language)
    {
        $language_key = ucwords(strtolower($language));

        $result = '';

        foreach ($this->languages as $lang) {
            if (in_array($language_key, explode(', ', $lang[4]))) {
                $result = $lang[0];
                break;
            }
        }

        return $result;
    }

    /*
     * Get ISO-639-2/t (three-letter codes) terminologic from language name
     *
     * @return (string)
     */
    public function code2tByLanguage($language)
    {
        $language_key = ucwords(strtolower($language));

        $result = '';

        foreach ($this->languages as $lang) {
            if (in_array($language_key, explode(', ', $lang[4]))) {
                $result = $lang[1];
                break;
            }
        }

        return $result;
    }

    /*
     * Get ISO-639-2/b (three-letter codes) bibliographic from language name
     *
     * @return (string)
     */
    public function code2bByLanguage($language)
    {
        $language_key = ucwords(strtolower($language));

        $result = '';

        foreach ($this->languages as $lang) {
            if (in_array($language_key, explode(', ', $lang[4]))) {
                $result = $lang[2];
                break;
            }
        }

        return $result;
    }

    /*
     * Get ISO-639-3 (three-letter codes) from language name
     *
     * @return (string)
     */
    public function code3ByLanguage($language)
    {
        $language_key = ucwords(strtolower($language));

        $result = '';

        foreach ($this->languages as $lang) {
            if (in_array($language_key, explode(', ', $lang[4]))) {
                $result = $lang[3];
                break;
            }
        }

        return $result;
    }
}
