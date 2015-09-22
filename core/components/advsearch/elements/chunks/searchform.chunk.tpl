<form id="[[+advsearch.asId]]_advsea-form" class="advsea-form" action="[[~[[+advsearch.landing]]]]" method="[[+advsearch.method]]">
  <fieldset>
    <input type="hidden" name="id" value="[[+advsearch.landing]]" />
    <input type="hidden" name="asId" value="[[+advsearch.asId]]" />
    [[+advsearch.helpLink]]<input type="text" id="[[+advsearch.asId]]_advsea-search" name="[[+advsearch.searchIndex]]" value="[[+advsearch.searchValue]]" />
    [[+advsearch.liveSearch:isnot=`1`:then=`<input type="submit" id="[[+advsearch.asId]]_advsea-submit"  name="sub" value="[[%advsearch.search? &namespace=`advsearch` &topic=`default`]]" />`:else`=``]]
  </fieldset>
</form>
[[+advsearch.resultsWindow]]