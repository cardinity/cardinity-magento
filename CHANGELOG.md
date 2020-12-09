# 2.1.0

## Changed

* Change payment currency from display currency to base currency, per [Magento documentation](https://docs.magento.com/m1/ce/user_guide/configuration/currency-setup.html). This might introduce some breaking changes in Cardinity payments if the store currencies are set differently than Magento suggests.