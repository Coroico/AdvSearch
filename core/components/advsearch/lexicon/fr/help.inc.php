<?php
/**
 * AdvSearch
 *
 *
 * AdvSearch is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * AdvSearch is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * AdvSearch; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package advsearch
 *
 * WARNING: The closing EOD; must be followed by a newline.
 */
/**
 * Help Lexicon Topic
 *
 * @package advsearch
 * @subpackage lexicon
 */

$_lang['advsearch.help'] = <<<EOD
<div id="advsea-help-content">
<h1>
  Advanced Search - Syntaxe de recherche
</h1>
<div id="minitoc-area">
<ul class="minitoc">
<li>
<a href="[[~[[*id]]]]#Intro">Introduction</a>
</li>
<li>
<a href="[[~[[*id]]]]#Terms">Termes</a>
</li>
<li>
<a href="[[~[[*id]]]]#Wildcard Searches">Caractères joker</a>
</li>
<li>
<a href="[[~[[*id]]]]#Boolean operators">Operateurs logiques</a>
<ul class="minitoc">
<li>
<a href="[[~[[*id]]]]#OR"> OR </a>
</li>
<li>
<a href="[[~[[*id]]]]#AND"> AND </a>
</li>
<li>
<a href="[[~[[*id]]]]#NOT"> NOT </a>
</li>
</ul>
</li>
<li>
<a href="[[~[[*id]]]]#Grouping">Groupement</a>
</li>
</ul>
</div>

<a name="Intro"></a>
<h2 class="boxed">Introduction</h2>

<div class="section">
<p>Voici quelques explications pour employer le moteur de recherche de manière efficace.</p>
</div>

<a name="N10032"></a><a name="Terms"></a>
<h2 class="boxed">Termes</h2>
<div class="section">
<p>Une requête est constituée par des termes ou mots-clés et des opérateurs. Il y a 2 types de termes : Simples ou Phrases.</p>
<p>Un mot simple est un mot unique tel que "test" ou "hello".</p>
<p>Une phrase est un groupe de mots encadrés par des guillemets "hello dolly".</p>
<p>Des combinaisons de termes multiples sont possibles grâce aux opérateurs logiques.</p>
</div>

<a name="N1006D"></a><a name="Wildcard Searches"></a>
<h2 class="boxed">Caractères "jokers"</h2>
<p>AdvSearch supporte des caractères jokers pour les recherches sur mots simples ou multiples (mais pas sur les "phrases")</p>
<p>Remplacez <em><strong>un</strong></em> caractère quelconque par le point d'interrogation "?".</p>
<p>Remplacez plusieurs caractères consécutifs par l'astérisque "*".</p>
<p>Exemples:</p>
<p>Rechercher tous termes s'écrivant "te", puis "n'importequoi", puis "t".</p>
<pre class="code">te?t</pre>
<p>Rechercher tous termes débutant par "test" et continuant par toutes suites possibles.</p>
<pre class="code">test*</pre>
<p>Ou bien avec un ou plusieurs caractères queconques en plein milieu.</p>
<pre class="code">te*t</pre>
<p>Note: Il n'est PAS possible de placer ces caractères jokers en DEBUT de terme.</p>

<a name="N100FA"></a><a name="Boolean operators"></a>
<h2 class="boxed"> Operateurs Logiques</h2>
<div class="section">
<p>Il s'agit d'opérateurs permettant de combiner des termes.
        AdvSearch supporte AND, OR, and NOT comme opérateurs logiques (Note: Les opérateurs doivent être en majuscules).</p>

<a name="N10103"></a><a name="OR"></a>
<h3 class="boxed">OR</h3>
<p> OR (ou) Les résultats comporteront des réponses pour chacun des termes demandés  liés par des OR. Remarque: les caractères "double-pipe"  || peuvent remplacer OR.</p>
<p>"jakarta apache" jakarta</p>

<p>or</p>
<pre class="code">"jakarta apache" OR jakarta</pre>

<a name="N10116"></a><a name="AND"></a>
<h3 class="boxed">AND</h3>
<p>AND (et) Il faut que les contenus remontés en résultats comportent les DEUX termes (ou plus) . && peut remplacer AND.</p>
<p>Ceci cherchera des contenu avec "jakarta apache" et "Apache Lucene" obligatoirement. </p>
<pre class="code">"jakarta apache" AND "Apache Lucene"</pre>

<a name="N10136"></a><a name="NOT"></a>
<h3 class="boxed">NOT</h3>
<p> NOT (négation) va exclure des résultats contenant tout sauf ce qui suit l'opérateur NOT. Le caractère "!" peut remplacer NOT.</p>
<p>"jakarta apache" NOT "Apache Lucene"</p>

<p>Note:  NOT ne peut être employé avec un seul terme. Par exemple, ceci ne peut pas fonctionner:</p>
<pre class="code">NOT "jakarta apache"</pre>
</div>

<a name="N1015D"></a><a name="Grouping"></a>
<h2 class="boxed">Groupement</h2>
<div class="section">
<p>Avec des parenthèses on peut regrouper plusieurs groupes logiques.</p>
<p>Rechercher soit ("jakarta" OU "apache") ET "website" (résultats comportant toujours "website" combinés avec "jakarta" ou "apache"):</p>
<pre class="code">(jakarta OR apache) AND website</pre>
<p>Ceci élimine toute confusion et oblige à ce que le terme "website" existe ainsi que "jakarta" ou "apache".</p>
</div>

</div>
<!--+ end content +-->
EOD;
//don't remove this line. The closing EOD; must be followed by a newline. 