# Extended Deduplication (X-Dedupe)

!!! Automated merges on CiviCRM contacts can be *very* hard to undo, so be sure to have you database backed up before experimenting. Ideally you would work on a test system until you know what you're doing.


The extended deduplication extension was created to overcome a couple of 
shortcomings of CiviCRM's built-in deduplication feature:

## 1. Performance
Larger datasets can quickly bring the built-in deduplication
to its limits (and your server to its knees). Since XDedupe follows another
general approach, it should perform a lot better.

## 2. Automatic Conflict Resolution
When the built-in deduplication cannot resolve conflicts in a trivial way, 
it falls back to manual resolution. This can be pretty tedious, especially
if you have to fix the same pattern over and over. In X-Dedupe you have
various built-in conflict resolvers you can add to your configuration,
and even create your own - see below.

## 3. Documentation
If a conflict resolver (see above) has changed or dropped anything from
a contact to enable automatic merges, it will add an appropriate note
to the "Contact Merged" activity.

## 4. Modularity
All different components (see below) of this framework are purely 
addressed by their interface, and you can combine them freely - 
even if it doesn't make any sense. All modules also come with their 
own description of what they're doing, so you can always have an 
idea about what would happen.

## 5. Extensibility
The extension provides Symfony hooks for you to add your own 
implementations of the various module types, so you can add this
missing puzzle piece yourself, so it gives you the best results.

## 6. Automation

X-Dedupe knows three stages of automation:

0. **Individual execution**: While you're "playing" with the configuration, or you want to
have full control, you can pick individual tuples from the list
and to try to merge them on the spot.
1. **Manual execution**: you can experiment with a configuration
and once you're confident it does the right thing, you can hit the 
"merge all" button.
2. **Blind execution**: Once you have a configuration that's trustworthy, you can set it
"executable" so other people can just run it, without having to bother
with the details.
3. **Automatic Execution**: If you have a reliable configuration that you'd want to run on a 
regular basis, you can even schedule a configuration to be triggered
by CiviCRM's scheduled jobs.

## 7. Multi-Merge

When X-Dedupe's rules generate more than two contacts that would be considered 
duplicates, it simply generates larger groups of contacts to be merged
in one go.


!!! It's worth noticing, that X-Dedupe still uses CiviCRM's built-in merge facilities, so the actual merge should still insure data integrity within CiviCRM. 

# Read More
* [X-Dedupe's Workflow](workflow.md)
* [Module Types](module-types.md)
* [Extensibility](extensibility.md)

