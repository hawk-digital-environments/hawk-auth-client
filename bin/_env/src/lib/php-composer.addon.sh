areComposerDependenciesInstalled(){
  if [ -f ${PROJECT_ROOT_DIR}/vendor/autoload.php ]; then
    return
  fi

  false
}
