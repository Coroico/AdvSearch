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
	Advanced Search - Query Syntax
</h1>
<div id="minitoc-area">
<ul class="minitoc">
<li>
<a href="[[~[[*id]]]]#Intro">Introduction</a>
</li>
<li>
<a href="[[~[[*id]]]]#Terms">Terms</a>
</li>
<li>
<a href="[[~[[*id]]]]#Wildcard Searches">Wildcard Searches</a>
</li>
<li>
<a href="[[~[[*id]]]]#Boolean operators">Boolean Operators</a>
<ul class="minitoc">
<li>
<a href="[[~[[*id]]]]#OR"> &#79;&#82; </a>
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
<a href="[[~[[*id]]]]#Grouping">Grouping</a>
</li>
</ul>
</div>

<a name="Intro"></a>
<h2 class="boxed">Introduction</h2>

<div class="section">
<p>Find below the syntax of AdvSearch to set up powerfull search queries.</p>
</div>

<a name="N10032"></a><a name="Terms"></a>
<h2 class="boxed">Terms</h2>
<div class="section">
<p>A query is broken up into terms and operators. There are two types of terms: Single Terms and Phrases.</p>
<p>A Single Term is a single word such as "test" or "hello".</p>
<p>A Phrase is a group of words surrounded by double quotes such as "hello dolly".</p>
<p>Multiple terms can be combined together with Boolean operators to form a more complex query (see below).</p>
</div>

<a name="N1006D"></a><a name="Wildcard Searches"></a>
<h2 class="boxed">Wildcard Searches</h2>
<p>AdvSearch supports single and multiple character wildcard searches within single terms(not within phrase queries).</p>
<p>To perform a single character wildcard search use the "?" symbol.</p>
<p>To perform a multiple character wildcard search use the "*" symbol.</p>
<p>The single character wildcard search looks for terms that match that with the single character replaced. For example, to search for "text" or "test" you can use the search:</p>
<pre class="code">te?t</pre>
<p>Multiple character wildcard searches looks for 0 or more characters. For example, to search for test, tests or tester, you can use the search: </p>
<pre class="code">test*</pre>
<p>You can also use the wildcard searches in the middle of a term.</p>
<pre class="code">te*t</pre>
<p>Note: You cannot use a * or ? symbol as the first character of a search.</p>

<a name="N100FA"></a><a name="Boolean operators"></a>
<h2 class="boxed">Boolean Operators</h2>
<div class="section">
<p>Boolean operators allow terms to be combined through logic operators.
        AdvSearch supports AND, OR, and NOT as Boolean operators (Note: Boolean operators must be ALL CAPS).</p>

<a name="N10103"></a><a name="OR"></a>
<h3 class="boxed">OR</h3>
<p>The OR operator is the default conjunction operator. This means that if there is no Boolean operator between two terms, the OR operator is used.
        The OR operator links two terms and finds a matching document if either of the terms exist in a document. This is equivalent to a union using sets.
        The symbol || can be used in place of the word OR.</p>
<p>To search for documents that contain either "jakarta apache" or just "jakarta" use the query:</p>
<pre class="code">"jakarta apache" jakarta</pre>
<p>or</p>
<pre class="code">"jakarta apache" OR jakarta</pre>

<a name="N10116"></a><a name="AND"></a>
<h3 class="boxed">AND</h3>
<p>The AND operator matches documents where both terms exist anywhere in the text of a single document.
        This is equivalent to an intersection using sets. The symbol &amp;&amp; can be used in place of the word AND.</p>
<p>To search for documents that contain "jakarta apache" and "Apache Lucene" use the query: </p>
<pre class="code">"jakarta apache" AND "Apache Lucene"</pre>

<a name="N10136"></a><a name="NOT"></a>
<h3 class="boxed">NOT</h3>
<p>The NOT operator excludes documents that contain the term after NOT.
        This is equivalent to a difference using sets. The symbol ! can be used in place of the word NOT.</p>
<p>To search for documents that contain "jakarta apache" but not "Apache Lucene" use the query: </p>
<pre class="code">"jakarta apache" NOT "Apache Lucene"</pre>
<p>Note: The NOT operator cannot be used with just one term. For example, the following search will return no results:</p>
<pre class="code">NOT "jakarta apache"</pre>
</div>

<a name="N1015D"></a><a name="Grouping"></a>
<h2 class="boxed">Grouping</h2>
<div class="section">
<p>AdvSearch supports using parentheses to group clauses to form sub queries. This can be very useful if you want to control the boolean logic for a query.</p>
<p>To search for either "jakarta" or "apache" and "website" use the query:</p>
<pre class="code">(jakarta OR apache) AND website</pre>
<p>This eliminates any confusion and makes sure you that website must exist and either term jakarta or apache may exist.</p>
</div>

</div>
<!--+ end content +-->
EOD;
//don't remove this line. The closing EOD; must be followed by a newline. 