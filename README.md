# Distribution Bundle

Collection of composer handlers to prepare code to distribution

## Usage

    {
        "scripts": {
            "post-install-cmd": [
                "Bankiru\\DistributionBundle\\Composer\\NpmHandler::install",
                "Bankiru\\DistributionBundle\\Composer\\NodeModuleHandler::bowerInstall",
                "Bankiru\\DistributionBundle\\Composer\\NodeModuleHandler::grunt",
                "Bankiru\\DistributionBundle\\Composer\\CleanHandler::cleanAll"
            ],
            "post-update-cmd": [
                "Bankiru\\DistributionBundle\\Composer\\NpmHandler::update",
                "Bankiru\\DistributionBundle\\Composer\\NodeModuleHandler::bowerUpdate",
                "Bankiru\\DistributionBundle\\Composer\\NodeModuleHandler::grunt",
                "Bankiru\\DistributionBundle\\Composer\\CleanHandler::cleanAll"
            ]
        },
        "extra": {
            "...": "..."
        }
    }

## Handlers

### Composer/CleanHandler

CleanHandler removes development files, tests and build tools. By default it skip running in dev mode.
This can be customized in composer.json with parameter `clean-in-dev` in section `extra`

    {
        "extra": {
            "clean-in-dev": true
        }
    }

#### cleanVcsMeta

Removes version control systems metadata in root directory and all it subdirectories. Eg. .git\*, .hg\*, .svc\*, .csv\*
Can be customized in composer.json with parameter `clean-vcs-meta-patterns` in section `extra`

    {
        "extra": {
            "clean-vcs-meta-patterns": [".git*"]
        }
    }

#### cleanTests

Removes all tests directories in root directory and all it subdirectories.

#### cleanCustom

Removes all files and directories specified in composer.json with parameter `clean-custom` in section `extra`

    {
        "extra": {
            "clean-custom": ["build", "somedir/file-to-remove"]
        }
    }

#### cleanAll

Runs all cleans.

### Composer/NpmHandler

Runs npm (NodeJS Package Manager). Available to commands: `install` and `update`.

You can control when to run npm by specifying parameter `npm-run-condition` in section `extra` of composer.json .

It use [symfony/expression-language](http://symfony.com/doc/current/components/expression_language/index.html).

For example, skip executing npm if UNITTEST environment variable defined

    {
        "extra": {
            "npm-run-condition": "getenv('UNITTEST') == false"
        }
    }

### Composer/NodeModuleHandler

Executes one of node modules. Now supports [grunt](http://gruntjs.com/) and [bower](http://bower.io/).

Path to node_modules directory can be customized in composer.json with parameter `node_nodules-dir` in section `extra`.
Default value is `./node_modules`.

#### grunt

Executes grunt with arguments dependent of env.

Arguments can be customized in composer.json with parameter `grunt-args` in section `extra`.

- `grunt-work-dir` directory where Gruntfile.js placed
- `grunt-run-condition` same as `npm-run-condition`.
- `grunt-fail-on-warning` needs to stop build in case when concat task warns about non-existent file.

Default parameters are:

    {
        "extra": {
            "grunt-work-dir"        : ".",
            "grunt-args"            : {"prod": "prod", "dev": "dev"}
            "grunt-run-condition"   : null,
            "grunt-fail-on-warning" : false
        }
    }


#### bowerInstall and bowerUpdate

Executes `bower install` or `bower update`.

Has two parameters:

- `bower-work-dir` directory where bower.json placed
- `bower-run-condition` same as `npm-run-condition`.
