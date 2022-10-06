# Extensibility

There are 4 Symfony events you can use to add your own
implementations of one of the [4 module types](module-types.md). All
you have to do, is to provide a class name accessible to the auto loader. 

1. ``civi.xdedupe.finders`` - Finder modules
1. ``civi.xdedupe.filters`` - Filter modules
1. ``civi.xdedupe.resolvers`` - Resolver modules
1. ``civi.xdedupe.pickers`` - Picker modules

This allows you implement very specific workflows to suit your
organisation's needs.

But it also enables extension developers to ship deduping "instructions"
with regards to their custom datastructures along with the extension. This could 
be new criteria to be used to identify potential duplicates ("finders"), for 
example a special ID supplied by this extension. Or, for example, 
it could provide the instructions on how to properly and consistently
treat this custom data in case of a contact merge (resolvers).

You can have a look at an example where custom modules are being 
added by another extension 
[HERE](https://github.com/systopia/com.proveg.mods/blob/master/CRM/Xdedupe/ProVeg.php).