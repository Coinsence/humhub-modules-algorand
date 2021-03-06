# Algorand Module ![GitHub](https://img.shields.io/github/license/Coinsence/coinsence-monorepo.svg) [![Build Status](https://travis-ci.org/Coinsence/humhub-modules-alogrand.svg?branch=master)](https://travis-ci.org/Coinsence/humhub-modules-alogrand) [![Coverage Status](https://coveralls.io/repos/github/Coinsence/humhub-modules-alogrand/badge.svg?branch=master)](https://coveralls.io/github/Coinsence/humhub-modules-alogrand?branch=master)


Algorand module ensure smart contracts integration with [humhub-modules-xcoin](https://github.com/Coinsence/humhub-modules-xcoin).


# Table of content

- **[Overview](#Overview)**
- **[Development](#Development)**
	- **[Installation](#0)**
	- **[Testing](#1)**

# Overview 
 
Algorand module represents a connector between [Xcoin](https://github.com/Coinsence/humhub-modules-xcoin) and Blockchain [Smart Contract](https://github.com/Coinsence/coinsence-monorepo). 

This module will not be functional without **Xcoin Module**, in order to install this latter check its [documentation](https://github.com/Coinsence/humhub-modules-xcoin).

--- 

Principal calls made through this module : 

*	`POST /coin/transfer` to transfer coins 
*	`POST /coin/balance` to get a wallet balance
* 	`GET /api/getAlgoBalance` to get a wallet AlgoBalance
* 	`POST /asset` to create an asset
*	`POST /wallet` to create wallet

# Development 

### Installation 

Two ways are possible : 

- External Installation (recommended for development purpose) : 

	Clone the module outside your [Humhub](http://docs.humhub.org/admin-installation.html) root directory for example in a folder called `modules` : 

		 $ cd modules 
   		 $ git clone https://github.com/Coinsence/humhub-modules-alogrand.git

	Configure `Autoload` path by adding this small code block in the `humhub_root_direcotry/protected/config/common.php` file : 

```injectablephp
return [
	'params' => [
		'moduleAutoloadPaths' => ['/path/to/modules'],
	],
];
```

- Internal Installation (recommended for direct usage purpose) :

	Just clone the module directly under `humhub_root_direcotry/protected/humhub/modules` 
    
=> Either ways you need to enable the module through through *Browse online* tab in the *Administration menu* under modules section.

### Testing

TBD