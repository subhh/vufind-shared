# VuFind Shared Components

VuFind Shared is copyright (c) by Staats- und Universitätsbibliothek Hamburg and released under the terms of the GNU
General Public License v3.

## Description

A collection shared components used by our VuFind installations.

### PruneSolrFieldFacetListener

VuFindSearch listener that removes values from a Solr field facet. This listener attaches to the ```.post``` event and
removes facet values that are not acceptable.

## Authors

David Maus &lt;david.maus@sub.uni-hamburg.de&gt;
