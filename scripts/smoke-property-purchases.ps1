# Smoke test for the property purchase simulation.
#
# What this does:
# - Creates or refreshes the 100 funded simulation users.
# - Uses a small subset of those users so the run is quick.
# - Places simulated property bids without waiting in real time.
# - Spreads the generated bid timestamps over the configured minutes.
#
# Usage from the project root:
#   .\scripts\smoke-property-purchases.ps1
#
# Optional examples:
#   .\scripts\smoke-property-purchases.ps1 -PropertyId 2
#   .\scripts\smoke-property-purchases.ps1 -Users 10 -MinAmount 250 -MaxAmount 1500

param (
    # Property to fund. Change this if property 1 is already fully funded.
    [int] $PropertyId = 1,

    # Number of simulation users to involve in the smoke run.
    [int] $Users = 5,

    # Fake duration used for generated bid timestamps.
    [int] $Minutes = 1,

    # Smallest simulated purchase amount.
    [int] $MinAmount = 500,

    # Largest simulated purchase amount.
    [int] $MaxAmount = 1000
)

php artisan simulation:property-purchases $PropertyId `
    --seed-users `
    --no-wait `
    --users=$Users `
    --minutes=$Minutes `
    --min=$MinAmount `
    --max=$MaxAmount
