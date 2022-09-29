# Extended Deduplication System ( ``xdedupe``)

The extension is licensed under [AGPL-3.0](LICENSE.txt).

This extension offers a modular and flexible alternative to CiviCRM's
built-in deduplication system. It is designed to be
* fast and scalable: CiviCRM's built-in solution tends to run into problems with large data sets
* highly configurable: you have almost 60 modules to be used for your configuration, and there's new ones being added all the time.
* automated: once you have worked out a configuration that is rock solid, you can schedule it to be executed automatically on a regular basis
* offers automated conflict resolution modules

## Warning

Automated merges on CiviCRM contacts can be *very* hard to undo, so
be sure to have you database backed up before experimenting. Ideally
you would work on a separate test system until you know what you're doing.


## Requirements

* PHP v7.4+
* CiviCRM 5.29+

## Installation

This is an extension not (yet) listed by default in the CiviCRM extension panel. Please refer to the
[CiviCRM documentation](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension)
on how to install such an extension.

Once installed, you can access the X-Dedupe system via the "Administer => Automation" menu.

# Control Room

Note: The following is a quick readme, you can find a more thorough documentation [HERE](docs/index.md).

The control room lets you create, refine, test, and apply a particular 
deduplication configuration.

![Control Room](docs/img/control_room.png)

**For more details please refer to the documentation [HERE](docs/index.md).**

# Configuration Manager

![Configuration Manager](docs/img/configuration_manager.png)

The configuration manager gives you an overview of all your saved X-Dedupe
configurations. You can then decide to enable each of them:
1. for manual, supervised execution via the control panel
2. for manual, automated execution, i.e. by clicking a button.
3. for scheduled automated execution, e.g. if you have a safe(!) cleanup routine to run every night 


## Known Issues

You will need the ``administer CiviCRM`` permission to use this extension, since
it allows profound and automated changes to be applied to the database.
