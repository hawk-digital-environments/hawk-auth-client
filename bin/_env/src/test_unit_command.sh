if ! areComposerDependenciesInstalled ; then
  dockerSsh ${DEFAULT_SERVICE_NAME} "composer install"
fi

ARGS="${other_args[*]}"

if [[ ${args[--coverage-text]} ]]; then
  dockerSsh ${DEFAULT_SERVICE_NAME} "composer run test:unit:coverage:text $ARGS"
elif [[ ${args[--coverage]} ]]; then
  dockerSsh ${DEFAULT_SERVICE_NAME} "composer run test:unit:coverage $ARGS"
else
  dockerSsh ${DEFAULT_SERVICE_NAME} "composer run test:unit $ARGS"
fi
