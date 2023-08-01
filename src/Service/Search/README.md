# SearchService

The search service is the basic input / output for searching documents in the system.

The service/method `SearchService->search` will allow you to search for a certain term and an optional search
configuration.

This configuration is a `Search/Config` instance where different search settings can be set. For instance, you can
set a date-range, a specific document type, limit, offset etc.

The output will be a `Search/Result/Result` object. This object contains the total number of results, the total number of
pages, aggregations, suggestions and the actual results. These results are typed with the interface `ResultEntry` and
could be a `Document`, `Page`, or other search result entity.

If the `$result->hasFailed()` returns `true`, an error message can be retrieved from `$result->getMessage()`.
If there are 0 entries, the `$result->isEmpty()` will return `true`.

Note that a result with 0 entries is NOT directly the same as a failed result. A successful result can have 0 results,
but a failed result can never have results.

The results are a collection of `Search/Entry` objects. It contains the document as found in the DATABASE (not
elasticsearch), and the highlights for the search term. NOTE THAT ALL INFORMATION IS FETCHED FROM THE DATABASE ENTITY,
NOT DIRECTLY FROM ELASTICSEARCH. THIS MEANS THAT ELASTIC IS PURELY FOR SEARCHING RECORDS, NOT FOR STORING THEM.

The aggregations are a collection of `Search/Aggregation` objects. These objects contain the aggregation name and
the buckets in `AggregationBucketEntry` objects.

The suggestions are a collection of `Search/Suggestion` objects. These objects contain the suggestion(s) name and
the actual suggestions from `SuggestionEntry` objects.
