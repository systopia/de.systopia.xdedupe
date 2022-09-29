# Components

The deduplication workflow consists of 5 different module types. 
These module types have countless implementations from which you
can compile your own specialised deduplication configurations.

Each module comes with it's own documentation, that is accessible
from within the extensions's UI, so should get a clear picture 
about what would happen if you run your configuration.

## Finders

Finders are modules that can provide an aspect of a contact (e.g.
last name) where equality would indicate a potential duplicate. Usually,
you'll have to combine a couple of those get to a certain
precision.

Finders are used to generate a performant SQL query to identify 
*potential* duplicates.

## Filters

Filter modules will help to narrow down the list of potential
duplicates. They can either provide a SQL term to be added to
the search query, or add a php routine to filter algorithmically -
e.g. the name similarity filter is much more efficient this way.

## Pickers

Once an x-tuple[^1] has been identified, the master 
contact (i.e. the one that prevails in an upcoming merge)
has to be picked from the tuple. CiviCRM simply uses the lowest
contact ID, but some scenarios might call for other criteria. For 
example, someone implemented "most personalised activities", since 
that was an indicator that that contact's ID would've most likely been 
used in external communication. (Which should've been avoided in
the first place, of course).

## Resolvers

Resolvers will be called on an x-tuple[^1] to be merged. Their job
is to harmonise one aspect of the contacts (e.g. first name), 
so that CiviCRM's merge will merge without conflicts. This
is attempted after all resolvers have applied and documented 
their changes. Obviously, those changes will be rolled back 
automatically should the merge fail.

[^1]: An x-tuple is a group of two or more contacts that should be 
merged into 1.