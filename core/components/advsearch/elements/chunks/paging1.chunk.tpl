[[+advsearch.previouslink:isnot=``:then=`<span class="advsea-previous">
    <a href="[[+advsearch.previouslink]]">[[%advsearch.previous? &namespace=`advsearch` &topic=`default`]]</a>
</span>`]]
[[+advsearch.total:gt=`1`:then=`
<span class="advsea-current"> [[+advsearch.first]] - [[+advsearch.last]] / [[+advsearch.total]] </span>
`]]
[[+advsearch.nextlink:isnot=``:then=`<span class="advsea-next">
    <a href="[[+advsearch.nextlink]]">[[%advsearch.next? &namespace=`advsearch` &topic=`default`]]</a>
</span>`]]