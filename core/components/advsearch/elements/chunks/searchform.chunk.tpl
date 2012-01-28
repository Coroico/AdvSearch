<form id="[[+asId]]_advsea-form" class="advsea-form" action="[[~[[+landing]]]]" method="[[+method]]">
  <fieldset>
    <input type="hidden" name="id" value="[[+landing]]" />
    <input type="hidden" name="asId" value="[[+asId]]" />
    [[+helpLink]]<input type="text" id="[[+asId]]_advsea-search" name="[[+searchIndex]]" value="[[+searchValue]]" />
    [[+liveSearch:isnot=`1`:then=`<input type="submit" id="[[+asId]]_advsea-submit"  name="sub" value="[[%advsearch.search? &namespace=`advsearch` &topic=`default`]]" />`]]
  </fieldset>
</form>
[[+resultsWindow]]