<?php

use Matriphe\ISO639\ISO639;

class ISO639Test extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->iso = new ISO639();
    }

    public function testLanguageISO6391()
    {
        $this->assertSame('English', $this->iso->languageByCode1('en'));
        $this->assertSame('French', $this->iso->languageByCode1('fr'));
        $this->assertSame('Spanish', $this->iso->languageByCode1('es'));
        $this->assertSame('Indonesian', $this->iso->languageByCode1('id'));
        $this->assertSame('Javanese', $this->iso->languageByCode1('jv'));
        $this->assertSame('Hindi', $this->iso->languageByCode1('hi'));
        $this->assertSame('Thai', $this->iso->languageByCode1('th'));
        $this->assertSame('Korean', $this->iso->languageByCode1('ko'));
        $this->assertSame('Japanese', $this->iso->languageByCode1('ja'));
        $this->assertSame('Chinese', $this->iso->languageByCode1('zh'));
        $this->assertSame('Russian', $this->iso->languageByCode1('ru'));
        $this->assertSame('Arabic', $this->iso->languageByCode1('ar'));
        $this->assertSame('Vietnamese', $this->iso->languageByCode1('vi'));
        $this->assertSame('Malay', $this->iso->languageByCode1('ms'));
        $this->assertSame('Sundanese', $this->iso->languageByCode1('su'));
    }

    public function testNativeISO6391()
    {
        $this->assertSame('English', $this->iso->nativeByCode1('en'));
        $this->assertSame('français, langue française', $this->iso->nativeByCode1('fr'));
        $this->assertSame('español', $this->iso->nativeByCode1('es'));
        $this->assertSame('Bahasa Indonesia', $this->iso->nativeByCode1('id'));
        $this->assertSame('basa Jawa', $this->iso->nativeByCode1('jv'));
        $this->assertSame('हिन्दी, हिंदी', $this->iso->nativeByCode1('hi'));
        $this->assertSame('ไทย', $this->iso->nativeByCode1('th'));
        $this->assertSame('한국어, 조선어', $this->iso->nativeByCode1('ko'));
        $this->assertSame('日本語 (にほんご)', $this->iso->nativeByCode1('ja'));
        $this->assertSame('中文 (Zhōngwén), 汉语, 漢語', $this->iso->nativeByCode1('zh'));
        $this->assertSame('Русский', $this->iso->nativeByCode1('ru'));
        $this->assertSame('العربية', $this->iso->nativeByCode1('ar'));
        $this->assertSame('Việt Nam', $this->iso->nativeByCode1('vi'));
        $this->assertSame('bahasa Melayu, بهاس ملايو‎', $this->iso->nativeByCode1('ms'));
        $this->assertSame('Basa Sunda', $this->iso->nativeByCode1('su'));
    }

    public function testLanguageISO6392t()
    {
        $this->assertSame('English', $this->iso->languageByCode2t('eng'));
        $this->assertSame('French', $this->iso->languageByCode2t('fra'));
        $this->assertSame('Spanish', $this->iso->languageByCode2t('spa'));
        $this->assertSame('Indonesian', $this->iso->languageByCode2t('ind'));
        $this->assertSame('Javanese', $this->iso->languageByCode2t('jav'));
        $this->assertSame('Hindi', $this->iso->languageByCode2t('hin'));
        $this->assertSame('Thai', $this->iso->languageByCode2t('tha'));
        $this->assertSame('Korean', $this->iso->languageByCode2t('kor'));
        $this->assertSame('Japanese', $this->iso->languageByCode2t('jpn'));
        $this->assertSame('Chinese', $this->iso->languageByCode2t('zho'));
        $this->assertSame('Russian', $this->iso->languageByCode2t('rus'));
        $this->assertSame('Arabic', $this->iso->languageByCode2t('ara'));
        $this->assertSame('Vietnamese', $this->iso->languageByCode2t('vie'));
        $this->assertSame('Malay', $this->iso->languageByCode2t('msa'));
        $this->assertSame('Sundanese', $this->iso->languageByCode2t('sun'));
    }

    public function testNativeISO6392t()
    {
        $this->assertSame('English', $this->iso->nativeByCode2t('eng'));
        $this->assertSame('français, langue française', $this->iso->nativeByCode2t('fra'));
        $this->assertSame('español', $this->iso->nativeByCode2t('spa'));
        $this->assertSame('Bahasa Indonesia', $this->iso->nativeByCode2t('ind'));
        $this->assertSame('basa Jawa', $this->iso->nativeByCode2t('jav'));
        $this->assertSame('हिन्दी, हिंदी', $this->iso->nativeByCode2t('hin'));
        $this->assertSame('ไทย', $this->iso->nativeByCode2t('tha'));
        $this->assertSame('한국어, 조선어', $this->iso->nativeByCode2t('kor'));
        $this->assertSame('日本語 (にほんご)', $this->iso->nativeByCode2t('jpn'));
        $this->assertSame('中文 (Zhōngwén), 汉语, 漢語', $this->iso->nativeByCode2t('zho'));
        $this->assertSame('Русский', $this->iso->nativeByCode2t('rus'));
        $this->assertSame('العربية', $this->iso->nativeByCode2t('ara'));
        $this->assertSame('Việt Nam', $this->iso->nativeByCode2t('vie'));
        $this->assertSame('bahasa Melayu, بهاس ملايو‎', $this->iso->nativeByCode2t('msa'));
        $this->assertSame('Basa Sunda', $this->iso->nativeByCode2t('sun'));
    }

    public function testLanguageISO6392b()
    {
        $this->assertSame('English', $this->iso->languageByCode2b('eng'));
        $this->assertSame('French', $this->iso->languageByCode2b('fre'));
        $this->assertSame('Spanish', $this->iso->languageByCode2b('spa'));
        $this->assertSame('Indonesian', $this->iso->languageByCode2b('ind'));
        $this->assertSame('Javanese', $this->iso->languageByCode2b('jav'));
        $this->assertSame('Hindi', $this->iso->languageByCode2b('hin'));
        $this->assertSame('Thai', $this->iso->languageByCode2b('tha'));
        $this->assertSame('Korean', $this->iso->languageByCode2b('kor'));
        $this->assertSame('Japanese', $this->iso->languageByCode2b('jpn'));
        $this->assertSame('Chinese', $this->iso->languageByCode2b('chi'));
        $this->assertSame('Russian', $this->iso->languageByCode2b('rus'));
        $this->assertSame('Arabic', $this->iso->languageByCode2b('ara'));
        $this->assertSame('Vietnamese', $this->iso->languageByCode2b('vie'));
        $this->assertSame('Malay', $this->iso->languageByCode2b('may'));
        $this->assertSame('Sundanese', $this->iso->languageByCode2b('sun'));
    }

    public function testNativeISO6392b()
    {
        $this->assertSame('English', $this->iso->nativeByCode2b('eng'));
        $this->assertSame('français, langue française', $this->iso->nativeByCode2b('fre'));
        $this->assertSame('español', $this->iso->nativeByCode2b('spa'));
        $this->assertSame('Bahasa Indonesia', $this->iso->nativeByCode2b('ind'));
        $this->assertSame('basa Jawa', $this->iso->nativeByCode2b('jav'));
        $this->assertSame('हिन्दी, हिंदी', $this->iso->nativeByCode2b('hin'));
        $this->assertSame('ไทย', $this->iso->nativeByCode2b('tha'));
        $this->assertSame('한국어, 조선어', $this->iso->nativeByCode2b('kor'));
        $this->assertSame('日本語 (にほんご)', $this->iso->nativeByCode2b('jpn'));
        $this->assertSame('中文 (Zhōngwén), 汉语, 漢語', $this->iso->nativeByCode2b('chi'));
        $this->assertSame('Русский', $this->iso->nativeByCode2b('rus'));
        $this->assertSame('العربية', $this->iso->nativeByCode2b('ara'));
        $this->assertSame('Việt Nam', $this->iso->nativeByCode2b('vie'));
        $this->assertSame('bahasa Melayu, بهاس ملايو‎', $this->iso->nativeByCode2b('may'));
        $this->assertSame('Basa Sunda', $this->iso->nativeByCode2b('sun'));
    }

    public function testLanguageISO6393()
    {
        $this->assertSame('English', $this->iso->languageByCode3('eng'));
        $this->assertSame('French', $this->iso->languageByCode3('fra'));
        $this->assertSame('Spanish', $this->iso->languageByCode3('spa'));
        $this->assertSame('Indonesian', $this->iso->languageByCode3('ind'));
        $this->assertSame('Javanese', $this->iso->languageByCode3('jav'));
        $this->assertSame('Hindi', $this->iso->languageByCode3('hin'));
        $this->assertSame('Thai', $this->iso->languageByCode3('tha'));
        $this->assertSame('Korean', $this->iso->languageByCode3('kor'));
        $this->assertSame('Japanese', $this->iso->languageByCode3('jpn'));
        $this->assertSame('Chinese', $this->iso->languageByCode3('zho'));
        $this->assertSame('Russian', $this->iso->languageByCode3('rus'));
        $this->assertSame('Arabic', $this->iso->languageByCode3('ara'));
        $this->assertSame('Vietnamese', $this->iso->languageByCode3('vie'));
        $this->assertSame('Malay', $this->iso->languageByCode3('msa'));
        $this->assertSame('Sundanese', $this->iso->languageByCode3('sun'));
    }

    public function testNativeISO6393()
    {
        $this->assertSame('English', $this->iso->nativeByCode3('eng'));
        $this->assertSame('français, langue française', $this->iso->nativeByCode3('fra'));
        $this->assertSame('español', $this->iso->nativeByCode3('spa'));
        $this->assertSame('Bahasa Indonesia', $this->iso->nativeByCode3('ind'));
        $this->assertSame('basa Jawa', $this->iso->nativeByCode3('jav'));
        $this->assertSame('हिन्दी, हिंदी', $this->iso->nativeByCode3('hin'));
        $this->assertSame('ไทย', $this->iso->nativeByCode3('tha'));
        $this->assertSame('한국어, 조선어', $this->iso->nativeByCode3('kor'));
        $this->assertSame('日本語 (にほんご)', $this->iso->nativeByCode3('jpn'));
        $this->assertSame('中文 (Zhōngwén), 汉语, 漢語', $this->iso->nativeByCode3('zho'));
        $this->assertSame('Русский', $this->iso->nativeByCode3('rus'));
        $this->assertSame('العربية', $this->iso->nativeByCode3('ara'));
        $this->assertSame('Việt Nam', $this->iso->nativeByCode3('vie'));
        $this->assertSame('bahasa Melayu, بهاس ملايو‎', $this->iso->nativeByCode3('msa'));
        $this->assertSame('Basa Sunda', $this->iso->nativeByCode3('sun'));
    }

    public function testISO6391Language()
    {
        $this->assertSame('en', $this->iso->code1ByLanguage('English'));
        $this->assertSame('fr', $this->iso->code1ByLanguage('French'));
        $this->assertSame('es', $this->iso->code1ByLanguage('Spanish'));
        $this->assertSame('id', $this->iso->code1ByLanguage('Indonesian'));
        $this->assertSame('jv', $this->iso->code1ByLanguage('Javanese'));
        $this->assertSame('hi', $this->iso->code1ByLanguage('Hindi'));
        $this->assertSame('th', $this->iso->code1ByLanguage('Thai'));
        $this->assertSame('ko', $this->iso->code1ByLanguage('Korean'));
        $this->assertSame('ja', $this->iso->code1ByLanguage('Japanese'));
        $this->assertSame('zh', $this->iso->code1ByLanguage('Chinese'));
        $this->assertSame('ru', $this->iso->code1ByLanguage('Russian'));
        $this->assertSame('ar', $this->iso->code1ByLanguage('Arabic'));
        $this->assertSame('vi', $this->iso->code1ByLanguage('Vietnamese'));
        $this->assertSame('ms', $this->iso->code1ByLanguage('Malay'));
        $this->assertSame('su', $this->iso->code1ByLanguage('Sundanese'));
    }

    public function testISO6392tLanguage()
    {
        $this->assertSame('eng', $this->iso->code2tByLanguage('English'));
        $this->assertSame('fra', $this->iso->code2tByLanguage('French'));
        $this->assertSame('spa', $this->iso->code2tByLanguage('Spanish'));
        $this->assertSame('ind', $this->iso->code2tByLanguage('Indonesian'));
        $this->assertSame('jav', $this->iso->code2tByLanguage('Javanese'));
        $this->assertSame('hin', $this->iso->code2tByLanguage('Hindi'));
        $this->assertSame('tha', $this->iso->code2tByLanguage('Thai'));
        $this->assertSame('kor', $this->iso->code2tByLanguage('Korean'));
        $this->assertSame('jpn', $this->iso->code2tByLanguage('Japanese'));
        $this->assertSame('zho', $this->iso->code2tByLanguage('Chinese'));
        $this->assertSame('rus', $this->iso->code2tByLanguage('Russian'));
        $this->assertSame('ara', $this->iso->code2tByLanguage('Arabic'));
        $this->assertSame('vie', $this->iso->code2tByLanguage('Vietnamese'));
        $this->assertSame('msa', $this->iso->code2tByLanguage('Malay'));
        $this->assertSame('sun', $this->iso->code2tByLanguage('Sundanese'));
    }

    public function testISO6392bLanguage()
    {
        $this->assertSame('eng', $this->iso->code2bByLanguage('English'));
        $this->assertSame('fre', $this->iso->code2bByLanguage('French'));
        $this->assertSame('spa', $this->iso->code2bByLanguage('Spanish'));
        $this->assertSame('ind', $this->iso->code2bByLanguage('Indonesian'));
        $this->assertSame('jav', $this->iso->code2bByLanguage('Javanese'));
        $this->assertSame('hin', $this->iso->code2bByLanguage('Hindi'));
        $this->assertSame('tha', $this->iso->code2bByLanguage('Thai'));
        $this->assertSame('kor', $this->iso->code2bByLanguage('Korean'));
        $this->assertSame('jpn', $this->iso->code2bByLanguage('Japanese'));
        $this->assertSame('chi', $this->iso->code2bByLanguage('Chinese'));
        $this->assertSame('rus', $this->iso->code2bByLanguage('Russian'));
        $this->assertSame('ara', $this->iso->code2bByLanguage('Arabic'));
        $this->assertSame('vie', $this->iso->code2bByLanguage('Vietnamese'));
        $this->assertSame('may', $this->iso->code2bByLanguage('Malay'));
        $this->assertSame('sun', $this->iso->code2bByLanguage('Sundanese'));
    }

    public function testISO6393Language()
    {
        $this->assertSame('eng', $this->iso->code3ByLanguage('English'));
        $this->assertSame('fra', $this->iso->code3ByLanguage('French'));
        $this->assertSame('spa', $this->iso->code3ByLanguage('Spanish'));
        $this->assertSame('ind', $this->iso->code3ByLanguage('Indonesian'));
        $this->assertSame('jav', $this->iso->code3ByLanguage('Javanese'));
        $this->assertSame('hin', $this->iso->code3ByLanguage('Hindi'));
        $this->assertSame('tha', $this->iso->code3ByLanguage('Thai'));
        $this->assertSame('kor', $this->iso->code3ByLanguage('Korean'));
        $this->assertSame('jpn', $this->iso->code3ByLanguage('Japanese'));
        $this->assertSame('zho', $this->iso->code3ByLanguage('Chinese'));
        $this->assertSame('rus', $this->iso->code3ByLanguage('Russian'));
        $this->assertSame('ara', $this->iso->code3ByLanguage('Arabic'));
        $this->assertSame('vie', $this->iso->code3ByLanguage('Vietnamese'));
        $this->assertSame('msa', $this->iso->code3ByLanguage('Malay'));
        $this->assertSame('sun', $this->iso->code3ByLanguage('Sundanese'));
    }
}
