# county_code_directory_sample
A path resolution function from the Glenn County Code Directory module.

The County Code Directory is organized into five teirs, each with it's own content type and distinct fields for each parent content type. Tiers 2-4 were made optional and I was tasked with handling the changes to the routing for the search results. This function takes the nid of the search result and craws to the topmost level returning the title and path.
