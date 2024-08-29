<?php

/**
 * Test unitaire de la fonction propre du fichier inc/texte.php
 */

namespace Spip\Bonux\Tests\Texte;

use PHPUnit\Framework\TestCase;

class TouTypoTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		$GLOBALS['filtrer_javascript'] = 0;
		find_in_path('inc/texte.php', '', true);
	}

	protected function setUp(): void
	{
		$GLOBALS['meta']['type_urls'] = 'page';
		$GLOBALS['type_urls'] = 'page';
		// ce test est en fr
		changer_langue('fr');
	}

	/**
	 * @dataProvider providerTexteTOuTypo
	 */
	public function testTexteTOuTypo($expected, ...$args): void
	{
		$actual = _T_ou_typo(...$args);
		$this->assertSame($expected, $actual);
		$this->assertEquals($expected, $actual);
	}

	public function providerTexteTOuTypo(): array
	{
		return array(
			'string_1' =>
				array(
					'',
					'',
				),
			'string_2' =>
				array(
					'0',
					'0',
				),
			'string_3' =>
				array(
					'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
					'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
				),
			'string_4' =>
				array(
					'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
					'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
				),
			'string_5' =>
				array(
					'Un texte avec des entit&amp;eacute&nbsp;;s echap&amp;eacute&nbsp;; &amp;amp&nbsp;;&amp;lt&nbsp;;&amp;gt&nbsp;;&amp;quot&nbsp;;',
					'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
				),
			'string_6' =>
				array(
					'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
					'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
				),
			'string_7' =>
				array(
					'Un texte avec des entit&amp;#233&nbsp;;s num&amp;#233&nbsp;;riques echap&amp;#233&nbsp;;es &amp;#38&nbsp;;&amp;#60&nbsp;;&amp;#62&nbsp;;&amp;quot&nbsp;;',
					'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
				),
			'string_8' =>
				array(
					'Un texte sans entites &amp;&lt;>"&#8217;',
					'Un texte sans entites &<>"\'',
				),
			'string_9' =>
				array(
					'{{{Des raccourcis}}} {italique} {{gras}} <code class="spip_code spip_code_inline" dir="ltr">du code</code>',
					'{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
				),
			'string_10' =>
				array(
					'Un modele <tt>&lt;modeleinexistant|lien=[-&gt;https://www.spip.net]&gt;</tt>',
					'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
				),
			'string_11' =>
				array(
					'Un texte avec des retour
a la ligne et meme des

paragraphes',
					'Un texte avec des retour
a la ligne et meme des

paragraphes',
				),
			'string_w_idiome_1_default' =>
				array(
					'Cette information est obligatoire',
					'<:info_obligatoire:>',
				),
			'string_w_idiome_2_default' =>
				array(
					'Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'<:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
				),
			'string_w_idiome_3_default' =>
				array(
					'Une chaine de langue qui existe pas &lt;:modulerien:info_quinexistepas&nbsp;:> et donc',
					'Une chaine de langue qui existe pas <:modulerien:info_quinexistepas:> et donc',
				),
			'string_w_idiome_4_default' =>
				array(
					'Du texte d&#8217;abord et ensuite Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'Du texte d\'abord et ensuite <:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
				),
			'string_w_idiome_5_default' =>
				array(
					'Une chaine avec idiome et html non trivial (obligatoire) <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec idiome et html non trivial <:info_obligatoire_02:> <strong style="color:#000099">alors</strong>?',
				),
			'string_w_idiome_6_default' =>
				array(
					'<span class="apercu_texte" style="color:red">Cette information est obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><:info_obligatoire:></span>',
				),
			'string_w_idiome_7_default' =>
				array(
					'Obligatoire',
					'<multi>[fr]Obligatoire[en]Requested</multi>',
				),
			'string_w_idiome_8_default' =>
				array(
					'Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'<multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
				),
			'string_w_idiome_9_default' =>
				array(
					'Du texte d&#8217;abord et ensuite Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'Du texte d\'abord et ensuite <multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
				),
			'string_w_idiome_10_default' =>
				array(
					'Une chaine avec multi et html non trivial Obligatoire <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec multi et html non trivial <multi>[fr]Obligatoire[en]Requested</multi> <strong style="color:#000099">alors</strong>?',
				),
			'string_w_idiome_11_default' =>
				array(
					'<span class="apercu_texte" style="color:red">Obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><multi>[fr]Obligatoire[en]Requested</multi></span>',
				),
			'string_w_idiome_1_toujours' =>
				array(
					'Cette information est obligatoire',
					'<:info_obligatoire:>',
					'toujours',
				),
			'string_w_idiome_2_toujours' =>
				array(
					'Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'<:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'toujours',
				),
			'string_w_idiome_3_toujours' =>
				array(
					'Une chaine de langue qui existe pas &lt;:modulerien:info_quinexistepas&nbsp;:> et donc',
					'Une chaine de langue qui existe pas <:modulerien:info_quinexistepas:> et donc',
					'toujours',
				),
			'string_w_idiome_4_toujours' =>
				array(
					'Du texte d&#8217;abord et ensuite Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'Du texte d\'abord et ensuite <:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'toujours',
				),
			'string_w_idiome_5_toujours' =>
				array(
					'Une chaine avec idiome et html non trivial (obligatoire) <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec idiome et html non trivial <:info_obligatoire_02:> <strong style="color:#000099">alors</strong>?',
					'toujours',
				),
			'string_w_idiome_6_toujours' =>
				array(
					'<span class="apercu_texte" style="color:red">Cette information est obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><:info_obligatoire:></span>',
					'toujours',
				),
			'string_w_idiome_7_toujours' =>
				array(
					'Obligatoire',
					'<multi>[fr]Obligatoire[en]Requested</multi>',
					'toujours',
				),
			'string_w_idiome_8_toujours' =>
				array(
					'Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'<multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'toujours',
				),
			'string_w_idiome_9_toujours' =>
				array(
					'Du texte d&#8217;abord et ensuite Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'Du texte d\'abord et ensuite <multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'toujours',
				),
			'string_w_idiome_10_toujours' =>
				array(
					'Une chaine avec multi et html non trivial Obligatoire <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec multi et html non trivial <multi>[fr]Obligatoire[en]Requested</multi> <strong style="color:#000099">alors</strong>?',
					'toujours',
				),
			'string_w_idiome_11_toujours' =>
				array(
					'<span class="apercu_texte" style="color:red">Obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><multi>[fr]Obligatoire[en]Requested</multi></span>',
					'toujours',
				),
			'string_w_idiome_1_multi' =>
				array(
					'Cette information est obligatoire',
					'<:info_obligatoire:>',
					'multi',
				),
			'string_w_idiome_2_multi' =>
				array(
					'Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'<:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'multi',
				),
			'string_w_idiome_3_multi' =>
				array(
					'Une chaine de langue qui existe pas &lt;:modulerien:info_quinexistepas&nbsp;:> et donc',
					'Une chaine de langue qui existe pas <:modulerien:info_quinexistepas:> et donc',
					'multi',
				),
			'string_w_idiome_4_multi' =>
				array(
					'Du texte d&#8217;abord et ensuite Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'Du texte d\'abord et ensuite <:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'multi',
				),
			'string_w_idiome_5_multi' =>
				array(
					'Une chaine avec idiome et html non trivial (obligatoire) <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec idiome et html non trivial <:info_obligatoire_02:> <strong style="color:#000099">alors</strong>?',
					'multi',
				),
			'string_w_idiome_6_multi' =>
				array(
					'<span class="apercu_texte" style="color:red">Cette information est obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><:info_obligatoire:></span>',
					'multi',
				),
			'string_w_idiome_7_multi' =>
				array(
					'Obligatoire',
					'<multi>[fr]Obligatoire[en]Requested</multi>',
					'multi',
				),
			'string_w_idiome_8_multi' =>
				array(
					'Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'<multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'multi',
				),
			'string_w_idiome_9_multi' =>
				array(
					'Du texte d&#8217;abord et ensuite Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'Du texte d\'abord et ensuite <multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'multi',
				),
			'string_w_idiome_10_multi' =>
				array(
					'Une chaine avec multi et html non trivial Obligatoire <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec multi et html non trivial <multi>[fr]Obligatoire[en]Requested</multi> <strong style="color:#000099">alors</strong>?',
					'multi',
				),
			'string_w_idiome_11_multi' =>
				array(
					'<span class="apercu_texte" style="color:red">Obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><multi>[fr]Obligatoire[en]Requested</multi></span>',
					'multi',
				),
			'string_w_idiome_1_jamais' =>
				array(
					'Cette information est obligatoire',
					'<:info_obligatoire:>',
					'jamais',
				),
			'string_w_idiome_2_jamais' =>
				array(
					'<:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'<:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'jamais',
				),
			'string_w_idiome_3_jamais' =>
				array(
					'Une chaine de langue qui existe pas <:modulerien:info_quinexistepas:> et donc',
					'Une chaine de langue qui existe pas <:modulerien:info_quinexistepas:> et donc',
					'jamais',
				),
			'string_w_idiome_4_jamais' =>
				array(
					'Du texte d\'abord et ensuite <:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'Du texte d\'abord et ensuite <:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'jamais',
				),
			'string_w_idiome_5_jamais' =>
				array(
					'Une chaine avec idiome et html non trivial <:info_obligatoire_02:> <strong style="color:#000099">alors</strong>?',
					'Une chaine avec idiome et html non trivial <:info_obligatoire_02:> <strong style="color:#000099">alors</strong>?',
					'jamais',
				),
			'string_w_idiome_6_jamais' =>
				array(
					'<span class="apercu_texte" style="color:red"><:info_obligatoire:></span>',
					'<span class="apercu_texte" style="color:red"><:info_obligatoire:></span>',
					'jamais',
				),
			'string_w_idiome_7_jamais' =>
				array(
					'<multi>[fr]Obligatoire[en]Requested</multi>',
					'<multi>[fr]Obligatoire[en]Requested</multi>',
					'jamais',
				),
			'string_w_idiome_8_jamais' =>
				array(
					'<multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'<multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'jamais',
				),
			'string_w_idiome_9_jamais' =>
				array(
					'Du texte d\'abord et ensuite <multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'Du texte d\'abord et ensuite <multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'jamais',
				),
			'string_w_idiome_10_jamais' =>
				array(
					'Une chaine avec multi et html non trivial <multi>[fr]Obligatoire[en]Requested</multi> <strong style="color:#000099">alors</strong>?',
					'Une chaine avec multi et html non trivial <multi>[fr]Obligatoire[en]Requested</multi> <strong style="color:#000099">alors</strong>?',
					'jamais',
				),
			'string_w_idiome_11_jamais' =>
				array(
					'<span class="apercu_texte" style="color:red"><multi>[fr]Obligatoire[en]Requested</multi></span>',
					'<span class="apercu_texte" style="color:red"><multi>[fr]Obligatoire[en]Requested</multi></span>',
					'jamais',
				),
			'string_w_idiome_1_toujours_ecrire' =>
				array(
					'Cette information est obligatoire',
					'<:info_obligatoire:>',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_2_toujours_ecrire' =>
				array(
					'Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'<:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_3_toujours_ecrire' =>
				array(
					'Une chaine de langue qui existe pas &lt;:modulerien:info_quinexistepas&nbsp;:> et donc',
					'Une chaine de langue qui existe pas <:modulerien:info_quinexistepas:> et donc',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_4_toujours_ecrire' =>
				array(
					'Du texte d&#8217;abord et ensuite Cette information est obligatoire et aussi (obligatoire) alors&nbsp;?',
					'Du texte d\'abord et ensuite <:info_obligatoire:> et aussi <:info_obligatoire_02:> alors?',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_5_toujours_ecrire' =>
				array(
					'Une chaine avec idiome et html non trivial (obligatoire) <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec idiome et html non trivial <:info_obligatoire_02:> <strong style="color:#000099">alors</strong>?',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_6_toujours_ecrire' =>
				array(
					'<span class="apercu_texte" style="color:red">Cette information est obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><:info_obligatoire:></span>',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_7_toujours_ecrire' =>
				array(
					'Obligatoire',
					'<multi>[fr]Obligatoire[en]Requested</multi>',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_8_toujours_ecrire' =>
				array(
					'Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'<multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_9_toujours_ecrire' =>
				array(
					'Du texte d&#8217;abord et ensuite Ce champs est obligatoire et aussi une autre multi Obligatoire alors&nbsp;?',
					'Du texte d\'abord et ensuite <multi>[fr]Ce champs est obligatoire[en]This information is Requested</multi> et aussi une autre multi <multi>[fr]Obligatoire[en]Requested</multi> alors?',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_10_toujours_ecrire' =>
				array(
					'Une chaine avec multi et html non trivial Obligatoire <strong style="color:#000099">alors</strong>&nbsp;?',
					'Une chaine avec multi et html non trivial <multi>[fr]Obligatoire[en]Requested</multi> <strong style="color:#000099">alors</strong>?',
					'toujours',
					'',
					['espace_prive' => true],
				),
			'string_w_idiome_11_toujours_ecrire' =>
				array(
					'<span class="apercu_texte" style="color:red">Obligatoire</span>',
					'<span class="apercu_texte" style="color:red"><multi>[fr]Obligatoire[en]Requested</multi></span>',
					'toujours',
					'',
					['espace_prive' => true],
				),
			);
	}
}
