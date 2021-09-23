# unraid-newenckey
Drive encryption is one of Unraid's many good features. When you encrypt part or all of your array and cache, at some point you might end up wanting to change your unlock key. Just how often, would depend on your threat model (and on your level of paranoia).

At the time of creating this (6.8), Unraid does not have a UI for changing the unlock key.

Here is a small tool that will let you change your unlock key.
Your array must be started to use this tool.

Essentially, this tool validates the provided current key against your drives, and on all drives that can be unlocked with the current key, replaces it with the new one (in fact, it adds the new key to all of them, and upon success, removes the old key from all of them).

**Important:** The tool does not save the provided new (replacement) key on permanent storage. Make very sure you have it backed up, either in your memory (...) or on some permanent storage (not on the encrypted array :-) ). If you misplace the new key, your data is hosed.

Currently this script needs to be run from the command line. I may add a UI dialog if there's enough interest (and time) - although I'm pretty sure Limetech has this feature on their radar for some upcoming version.


    Usage:  unraid-newenckey [current-key-file] [new-key-file]

Both positional arguments are optional and may be omitted.
If provided, each of them is either the name of a file (containing a password/passphrase, or a binary key if you're into those as I am), or a single dash (-). 

For each of the arguments, if it is either omitted or specified as a dash, the respective key will be prompted for interactively.

Note: if you provide a key file with a passphrase you later intend to use interactively when starting the array (the typical use case on Unraid), make sure the file does not contain an ending newline. One good way to do that is to use "echo -n", e.g.:

      echo -n "My Good PassPhrase" > /tmp/mykeyfile

This code has been tested, but no warranty is expressed or implied. Use at your own risk.

With the above out of the way, please report any issues.


(c) 2019-2021 @doron - CC BY-SA 4.0

## Change Log:
```
2021-09-21 v0.8 	Package as plugin, install script version 0.8
			Add "usage" text
			Add underscore to valid characters

2021-08-16 v0.7 	Update for Unraid 6.10: lsblk has changed in an incompatible way. This version 0.7
			should be backwards compatible with previous Unraid versions.

2020-10-14 v0.6 	Change drive scan logic so it catches Unassigned Devices encrypted drives as well.

2019-12-07 v0.5 	Some code tidy-up

2019-11-24 v0.4 	Change script name
			Notify when cleaning up
			Enforce limited character set on new key

2019-11-21 v0.3 	Activate cleanup trap

2019-11-20 v0.2 	Initial versioning
```
