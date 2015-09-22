<span class="advsea-result-pages">[[%advsearch.result_pages? &namespace=`advsearch` &topic=`default`]]</span>
[[+advsearch.previouslink:isnot=``:then=`
    <span class="advsea-previous">
        <a href="[[+advsearch.previouslink]]">
            [[%advsearch.previous? &namespace=`advsearch` &topic=`default`]]
        </a>
    </span>
`:else=`
    <span class="advsea-current-page">[[%advsearch.previous? &namespace=`advsearch` &topic=`default`]]</span>
`]]
[[+advsearch.paging3]]
[[+advsearch.nextlink:isnot=``:then=`
    <span class="advsea-next">
        <a href="[[+advsearch.nextlink]]">
            [[%advsearch.next? &namespace=`advsearch` &topic=`default`]]
        </a>
    </span>
`:else=`
    <span class="advsea-current-page">[[%advsearch.next? &namespace=`advsearch` &topic=`default`]]</span>
`]]
