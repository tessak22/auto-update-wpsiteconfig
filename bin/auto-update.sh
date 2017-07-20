#!/bin/bash

MULTIDEV="update-wp"
SITENAME="scalewp.io"

UPDATES_APPLIED=false

# Stash Circle Artifacts URL
CIRCLE_ARTIFACTS_URL="$CIRCLE_BUILD_URL/artifacts/$CIRCLE_NODE_INDEX/$CIRCLE_ARTIFACTS"

# login to Terminus
echo -e "\nLogging into Terminus..."
terminus auth:login --machine-token=${TERMINUS_MACHINE_TOKEN}

# delete the multidev environment
#echo -e "\nDeleting the ${MULTIDEV} multidev environment..."
#terminus multidev:delete $SITE_UUID.$MULTIDEV --delete-branch --yes

# recreate the multidev environment
#echo -e "\nRe-creating the ${MULTIDEV} multidev environment..."
#terminus multidev:create $SITE_UUID.live $MULTIDEV

# check for upstream updates
echo -e "\nChecking for upstream updates on the ${MULTIDEV} multidev..."
php -f bin/slack_notify.php wordpress_updates

# the output goes to stderr, not stdout
UPSTREAM_UPDATES="$(terminus upstream:updates:list $SITE_UUID.$MULTIDEV  --format=list  2>&1)"

if [[ ${UPSTREAM_UPDATES} == *"no available updates"* ]]
then
    # no upstream updates available
    echo -e "\nNo upstream updates found on the ${MULTIDEV} multidev..."
    php -f bin/slack_notify.php wordpress_no_core_updates
else
    # making sure the multidev is in git mode
    echo -e "\nSetting the ${MULTIDEV} multidev to git mode"
    terminus connection:set $SITE_UUID.$MULTIDEV git

    # apply WordPress upstream updates
    echo -e "\nApplying upstream updates on the ${MULTIDEV} multidev..."
    CORE_UPDATES=$(terminus upstream:updates:list ${SITE_UUID}.${TERMINUS_ENV} --field=message | tr '\n' '')
    php -f bin/slack_notify.php wordpress_core_updates "${CORE_UPDATES}"
    php -f bin/slack_notify.php terminus_core_updates
    terminus upstream:updates:apply $SITE_UUID.$MULTIDEV --yes --updatedb --accept-upstream
    UPDATES_APPLIED=true

    terminus -n wp $SITE_UUID.$MULTIDEV -- core update-db
fi

# making sure the multidev is in SFTP mode
echo -e "\nSetting the ${MULTIDEV} multidev to SFTP mode"
terminus connection:set $SITE_UUID.$MULTIDEV sftp

# Wake pantheon SSH
terminus -n wp $SITE_UUID.$MULTIDEV -- cli version

# check for WordPress plugin updates
echo -e "\nChecking for WordPress plugin updates on the ${MULTIDEV} multidev..."
PLUGIN_UPDATES=$(terminus -n wp $SITE_UUID.$MULTIDEV -- plugin list --update=available --format=count)
echo $PLUGIN_UPDATES

if [[ "$PLUGIN_UPDATES" == "0" ]]
then
    # no WordPress plugin updates found
    echo -e "\nNo WordPress plugin updates found on the ${MULTIDEV} multidev..."
    php -f bin/slack_notify.php wordpress_no_plugin_updates
else
    # update WordPress plugins
    echo -e "\nUpdating WordPress plugins on the ${MULTIDEV} multidev..."
    PLUGINS_TO_UPDATE=$(terminus -n wp $SITE_UUID.$MULTIDEV -- plugin list --update=available --field=name | tr '\n' ',\ ' | rev | cut -c2- | rev)
    php -f bin/slack_notify.php wordpress_plugin_updates "${PLUGINS_TO_UPDATE}"
    php -f bin/slack_notify.php terminus_plugin_updates
    terminus -n wp $SITE_UUID.$MULTIDEV -- plugin update --all

    # wake the site environment before committing code
    echo -e "\nWaking the ${MULTIDEV} multidev..."
    terminus env:wake $SITE_UUID.$MULTIDEV

    # committing updated WordPress plugins
    echo -e "\nCommitting WordPress plugin updates on the ${MULTIDEV} multidev..."
    terminus env:commit $SITE_UUID.$MULTIDEV --force --message="Update WordPress plugins: ${PLUGINS_TO_UPDATE}"
    UPDATES_APPLIED=true
fi

# check for WordPress theme updates
echo -e "\nChecking for WordPress theme updates on the ${MULTIDEV} multidev..."
THEME_UPDATES=$(terminus -n wp $SITE_UUID.$MULTIDEV -- theme list --update=available --format=count)
echo $THEME_UPDATES

if [[ "$THEME_UPDATES" == "0" ]]
then
    # no WordPress theme updates found
    echo -e "\nNo WordPress theme updates found on the ${MULTIDEV} multidev..."
    php -f bin/slack_notify.php wordpress_no_theme_updates
else
    # update WordPress themes
    echo -e "\nUpdating WordPress themes on the ${MULTIDEV} multidev..."
    THEMES_TO_UPDATE=$(terminus -n wp $SITE_UUID.$MULTIDEV -- theme list --update=available --field=name | tr '\n' ',\ ' | rev | cut -c2- | rev)
    php -f bin/slack_notify.php wordpress_theme_updates "${THEMES_TO_UPDATE}"
    php -f bin/slack_notify.php terminus_theme_updates
    terminus -n wp $SITE_UUID.$MULTIDEV -- theme update --all

    # wake the site environment before committing code
    echo -e "\nWaking the ${MULTIDEV} multidev..."
    terminus env:wake $SITE_UUID.$MULTIDEV

    # committing updated WordPress themes
    echo -e "\nCommitting WordPress theme updates on the ${MULTIDEV} multidev..."
    terminus env:commit $SITE_UUID.$MULTIDEV --force --message="Update WordPress themes: ${THEMES_TO_UPDATE}"
    UPDATES_APPLIED=true
fi

if [[ "${UPDATES_APPLIED}" = false ]]
then
    # no updates applied
    echo -e "\nNo updates to apply..."
    php -f bin/slack_notify.php wizard_noupdates
else
    # updates applied, carry on
    php -f bin/slack_notify.php wizard_updates

    # ping the multidev environment to wake it from sleep
    echo -e "\nPinging the ${MULTIDEV} multidev environment to wake it from sleep..."
    curl -I "https://$MULTIDEV-wp-microsite.pantheonsite.io/"

    # backstop visual regression
    echo -e "\nRunning BackstopJS tests..."
    php -f bin/slack_notify.php visual

    # Backstop visual regression
    echo -e "\nRunning backstop reference..."

    backstop reference

    echo -e "\nRunning backstop test..."
    VISUAL_REGRESSION_RESULTS=$(backstop test || echo 'true')

    echo "${VISUAL_REGRESSION_RESULTS}"

    # Rsync files to CIRCLE_ARTIFACTS
    echo -e "\nRsyincing backstop_data files to $CIRCLE_ARTIFACTS..."
    rsync -rlvz backstop_data $CIRCLE_ARTIFACTS

    DIFF_REPORT="$CIRCLE_ARTIFACTS/backstop_data/html_report/index.html"
    if [ ! -f $DIFF_REPORT ]; then
        echo -e "\nDiff report file $DIFF_REPORT not found!"
        exit 1
    fi
    DIFF_REPORT_URL="$CIRCLE_ARTIFACTS_URL/backstop_data/html_report/index.html"

    cd -
    if [[ ${VISUAL_REGRESSION_RESULTS} == *"Mismatch errors found"* ]]
    then
        # visual regression failed
        echo -e "\nVisual regression tests failed! Please manually check the ${MULTIDEV} multidev..."
        php -f bin/slack_notify.php visual_different "${DIFF_REPORT_URL}"
        exit 1
    else
        # visual regression passed
        echo -e "\nVisual regression tests passed between the ${MULTIDEV} multidev and live."
        php -f bin/slack_notify.php visual_same

        # enable git mode on dev
        echo -e "\nEnabling git mode on the dev environment..."
        terminus connection:set $SITE_UUID.dev git

        # merge the multidev back to dev
        echo -e "\nMerging the ${MULTIDEV} multidev back into the dev environment (master)..."
        php -f bin/slack_notify.php pantheon_deploy dev
        terminus multidev:merge-to-dev $SITE_UUID.$MULTIDEV

	    # update WordPress database on dev
        echo -e "\nUpdating the WordPress database on the dev environment..."
	    terminus -n wp $SITE_UUID.dev -- core update-db

        # deploy to test
        echo -e "\nDeploying the updates from dev to test..."
        php -f bin/slack_notify.php pantheon_deploy "test"
        terminus env:deploy $SITE_UUID.test --sync-content --cc --note="Auto deploy of WordPress updates (core, plugin, themes)"

	    # update WordPress database on test
        echo -e "\nUpdating the WordPress database on the test environment..."
	    terminus -n wp $SITE_UUID.test -- core update-db

        # backup the live site
        echo -e "\nBacking up the live environment..."
        php -f bin/slack_notify.php pantheon_backup
        terminus backup:create $SITE_UUID.live --element=all --keep-for=30

        # deploy to live
        echo -e "\nDeploying the updates from test to live..."
        php -f bin/slack_notify.php pantheon_deploy "live"
        terminus env:deploy $SITE_UUID.live --cc --note="Auto deploy of WordPress updates (core, plugin, themes)"

	    # update WordPress database on live
        echo -e "\nUpdating the WordPress database on the live environment..."
	    terminus -n wp $SITE_UUID.live -- core update-db

        echo -e "\nVisual regression tests passed! WordPress updates deployed to live..."
        php -f bin/slack_notify.php wizard_done "${DIFF_REPORT_URL}"
    fi
fi
