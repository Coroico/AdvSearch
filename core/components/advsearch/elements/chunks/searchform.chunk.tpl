<form class="advsea-search-form" action="[[~[[+landing]]]]" method="[[+method]]">
  <fieldset>
    <input type="hidden" name="id" value="[[+landing]]" />
    <input type="hidden" name="asId" value="[[+asId]]" />
    [[+helpLink]]<input type="text" id="[[+asId]]_search" name="[[+searchIndex]]" value="[[+searchValue]]" />
    <input type="submit" name="sub" value="[[%advsearch.search? &namespace=`advsearch` &topic=`default`]]" />
  </fieldset>
</form>
[[+resultsWindow]]