#!/bin/bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")" && pwd)"
if ! command -v jq >/dev/null; then
	echo "Please install jq"
	exit 1
fi

COMPOSER_FILE="${SCRIPT_DIR}/../../composer.json"

OLD_VERSION=$(jq -e -r .version "${COMPOSER_FILE}")
echo "Old version: ${OLD_VERSION}"
echo "New version: ${NEW_VERSION}"

# replace `.` with `_` to build our namespace string
OLD_VERSION_WITH_UNDERSCORES=${OLD_VERSION//./_}
OLD_NAMESPACE_STRING="v${OLD_VERSION_WITH_UNDERSCORES}"

NEW_VERSION_WITH_UNDERSCORES=${NEW_VERSION//./_}
NEW_NAMESPACE_STRING="v${NEW_VERSION_WITH_UNDERSCORES}"

# replace version in `composer.json`
echo "Updating composer.json version"
UPDATED_JSON="$(jq --arg NEW_VERSION "${NEW_VERSION}" '.version = $NEW_VERSION' "${COMPOSER_FILE}")"

# replace autoload namespaces in `composer.json`
echo "Updating autoload namespaces in composer.json"
AUTOLOAD_PSR4="$(echo "${UPDATED_JSON}" | jq -e '.autoload["psr-4"]')"
AUTOLOAD_PSR4="$(echo "${AUTOLOAD_PSR4}" | sed "s/${OLD_NAMESPACE_STRING}/${NEW_NAMESPACE_STRING}/g")"

AUTOLOAD_DEV_PSR4="$(echo "${UPDATED_JSON}" | jq -e '.["autoload-dev"]["psr-4"]')"
AUTOLOAD_DEV_PSR4="$(echo "${AUTOLOAD_DEV_PSR4}" | sed "s/${OLD_NAMESPACE_STRING}/${NEW_NAMESPACE_STRING}/g")"

UPDATED_JSON="$(echo "${UPDATED_JSON}" | jq --argjson AUTOLOAD_PSR4 "${AUTOLOAD_PSR4}" --argjson AUTOLOAD_DEV_PSR4 "${AUTOLOAD_DEV_PSR4}" '.autoload["psr-4"] = $AUTOLOAD_PSR4 | .["autoload-dev"]["psr-4"] = $AUTOLOAD_DEV_PSR4')"
echo "${UPDATED_JSON}" > "${COMPOSER_FILE}"

# replace namespace in `./woocommerce/` directory; we're looking in PHP and JS files only
echo "Replacing instances of ${OLD_NAMESPACE_STRING} with ${NEW_NAMESPACE_STRING} in ./woocommerce/"
find "${SCRIPT_DIR}/../../woocommerce/" -type f \( -name '*.php' -o -name '*.js' \) -exec sed -i "s/${OLD_NAMESPACE_STRING}/${NEW_NAMESPACE_STRING}/g" {} \;

# replace namespace in `./tests/` directory
echo "Replacing instances of ${OLD_NAMESPACE_STRING} with ${NEW_NAMESPACE_STRING} in ./tests/"
find "${SCRIPT_DIR}/../../tests/" -type f -name '*.php' -exec sed -i "s/${OLD_NAMESPACE_STRING}/${NEW_NAMESPACE_STRING}/g" {} \;

# replace version number in `./woocommerce/class-sv-wc-plugin.php` file
echo "Replacing VERSION constant value in ./woocommerce/class-sv-wc-plugin.php file"
sed -i -e "s/public const VERSION = '${OLD_VERSION}';/public const VERSION = '${NEW_VERSION}';/g" "${SCRIPT_DIR}/../../woocommerce/class-sv-wc-plugin.php"

# replace version number in `./woocommerce-framework-plugin-loader-sample.php` file
echo "Replacing FRAMEWORK_VERSION constant value in ./woocommerce-framework-plugin-loader-sample.php file"
sed -i -e "s/public const FRAMEWORK_VERSION = '${OLD_VERSION}';/public const FRAMEWORK_VERSION = '${NEW_VERSION}';/g" "${SCRIPT_DIR}/../../woocommerce-framework-plugin-loader-sample.php"

# add new changelog heading on line 3
echo "Adding changelog heading for new version"
CHANGELOG_HEADING="$(date +%Y).nn.nn - version ${NEW_VERSION}\n"
sed -i "3i ${CHANGELOG_HEADING}" "${SCRIPT_DIR}/../../woocommerce/changelog.txt"
