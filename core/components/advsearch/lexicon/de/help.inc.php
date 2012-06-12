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
 * @language   German
 * @author     M. Gartner | bitego
 */

$_lang['advsearch.help'] = <<<EOD
<div id="advsea-help-content">
<h1>Advanced Search - Hilfe</h1>
<div id="minitoc-area">
<ul class="minitoc">
<li>
<a href="[[~[[*id]]]]#intro">Einf&uuml;hrung</a>
</li>
<li>
<a href="[[~[[*id]]]]#terms">Begriffe</a>
</li>
<li>
<a href="[[~[[*id]]]]#wildcardsearches">Platzhalter Suche (Joker Zeichen)</a>
</li>
<li>
<a href="[[~[[*id]]]]#booleanoperators">Logische Operatoren</a>
<ul class="minitoc">
<li>
<a href="[[~[[*id]]]]#or"> &#79;&#82; </a>
</li>
<li>
<a href="[[~[[*id]]]]#and"> AND </a>
</li>
<li>
<a href="[[~[[*id]]]]#not"> NOT </a>
</li>
</ul>
</li>
<li>
<a href="[[~[[*id]]]]#Grouping">Gruppieren von Suchbegriffen</a>
</li>
</ul>
</div>

<h2 class="boxed" id="intro">Einf&uuml;hrung</h2>

<div class="section">
<p>Untenstehend finden Sie eine Beschreibung der Syntax von Advanced Search um m&auml;chtige Suchabfragen zu erstellen.</p>
</div>

<a name="N10032"></a>
<h2 class="boxed" id="terms">Begriffe</h2>

<div class="section">
<p>Eine Abfrage wird unterteilt in Suchbegriffe und Operatoren. Es gibt zwei Arten von Suchbegriffen: einzelne Suchbegriffe und Phrasen.</p>
<p>Ein einzelner Suchbegriff ist ein einzelnes Wort wie zB. "Welt" oder "Hallo".</p>
<p>Eine Phrase ist eine Gruppe von W&ouml;rtern welche in Anf&uuml;hrungszeichen steht wie zB. "Hallo Welt".</p>
<p>Mehrere Suchbegriffe k&ouml;nnen mittels sogenannten Boolscher (oder logischer) Operatoren zu einer komplexeren Suche kombiniert werden (N&auml;heres weiter Unten).</p>
</div>

<a name="N1006D"></a>
<h2 class="boxed" id="wildcardsearches">Platzhalter Suche (Joker Zeichen)</h2>
<div class="section">
<p>Advanced Search unterst&uuml;tzt den Einsatz von Einfach- oder Mehrfachplatzhaltern innerhalb eines Suchbegriffes (ausgenommen innerhalb von Phrasen).</p>
<p>Um eine Einfachplatzhalter-Suche auszuf&uuml;hren, verwenden Sie das "?" Zeichen - f&uuml;r die Mehrfachplatzhalter-Suche das "*" Zeichen.</p>
<p>Die Einfachplatzhalter-Suche findet alle Begriffe nach Ersatz des Platzhalterzeichens mit einem einzelnen beliebigen Buchstaben. Um zB. gleichzeitig nach den Begriffen "Text" und "Test" zu Suchen, kann folgende Abfrage verwendet werden:</p>
<pre class="code">Te?t</pre>
<p>Mehrfachplatzhalter ersetzen 0 oder mehr Zeichen innerhalb eines Suchbegriffes. Um zB. nach den Begriffen "Test", "Tests" oder "Testfahrzeug" zu suchen, verwenden Sie folgende Abfrage:</p>
<pre class="code">Test*</pre>
<p>Sie k&ouml;nnen Platzhalter auch innerhalb von Suchbegriffen verwenden.</p>
<pre class="code">Te*t</pre>
<p>Hinweis: Sie k&ouml;nnen die Platzhalter-Symbole * oder ? nicht als erstes Zeichen einer Sucheingabe verwenden.</p>
</div>

<a name="N100FA"></a>
<h2 class="boxed" id="booleanoperators">Logische Operatoren</h2>
<div class="section">
<p>Logische Operatoren erm&ouml;glichen die Kombination von Suchbegriffen. Advanced Search unterst&uuml;tzt AND, OR, und NOT als Logische Operatoren (Anmerkung: Logische Operatoren m&uuml;ssen immer in GROSSBUCHSTABEN geschrieben werden).</p>

<a name="N10103"></a>
<h3 class="boxed" id="or">OR</h3>
<p>Der OR Operator ist der Standard-Operator. Wir zwischen zwei Suchbegriffen kein Operator angef&uuml;hrt, verwendet Advanced Search immer automatisch den OR Operator. Der OR Operator verkn&uuml;pft zwei Suchbegriffe und findet Dokumente die mindestens einen der angef&uuml;hrten Suchbegriffe enthalten. Anstelle des Schl&uuml;sselwortes OR kann auch das Symbol || (doppeltes Pipe Zeichen) verwendet werden.</p>
<p>Um nach Dokumenten zu suchen die entweder "Wien Berlin" oder nur "Wien" enthalten verwenden Sie folgende Abfrage:</p>
<pre class="code">"Wien Berlin" Wien</pre>
<p>oder</p>
<pre class="code">"Wien Berlin" OR Wien</pre>

<a name="N10116"></a>
<h3 class="boxed" id="and">AND</h3>
<p>Der AND Operator findet Dokumente in denen beide Suchbegriffe vorhanden sind. Anstelle des Schl&uuml;sselwortes AND kann auch das Symbol &amp;&amp; verwendet werden.</p>
<p>Um nach Dokumenten zu suchen die "Wien Berlin" und "Berlin Rom" enthalten verwenden Sie folgende Abfrage: </p>
<pre class="code">"Wien Berlin" AND "Berlin Rom"</pre>

<a name="N10136"></a>
<h3 class="boxed" id="not">NOT</h3>
<p>Der NOT Operator schlie&szlig;t Dokumente aus, die den Suchbegriff der dem Schl&uuml;sselwort NOT folgt enthalten. Anstelle des Schl&uuml;sselwortes NOT kann auch das Symbol ! (Ausrufungszeichen) verwendet werden.</p>
<p>Um nach Dokumenten zu suchen die zwar "Wien Berlin" enthalten jedoch nicht "Wien Rom" verwenden Sie folgende Abfrage: </p>
<pre class="code">"Wien Berlin" NOT "Wien Rom"</pre>
<p>Anmerkung: Der NOT Operator kann nicht in Verbindung mit einem einzelnen Suchbegriff verwendet werden. Das folgende Suchbeispiel wird keine Ergebnisse zur&uuml;ckliefern:</p>
<pre class="code">NOT "Wien Berlin"</pre>
</div>

<a name="N1015D"></a>
<h2 class="boxed" id="grouping">Gruppieren von Suchbegriffen</h2>
<div class="section">
<p>Advanced Search unterst&uuml;tzt die Verwendung von Klammern um Abfragen zu Gruppieren und Unter-Abfragen zu erstellen. Dies kann n&uuml;tzlich sein um logische Abfragen zu besser zu kontrollieren.</p>
<p>Um nach Dokumenten zu suchen die entweder "Wien" oder "Berlin" und "Rom" enthalten verwenden Sie folgende Abfrage:</p>
<pre class="code">(Wien OR Berlin) AND Rom</pre>
<p>Dies schlie&szlig;t Unklarheiten in der Abfrage aus und stellt sicher, dass der Begriffe "Rom" zwingend enthalten sein muss und zus&auml;tzlich entweder "Wien" oder "Berlin".</p>
</div>
</div>
<!--+ end content +-->
EOD;
//don't remove this line. The closing EOD; must be followed by a newline.
