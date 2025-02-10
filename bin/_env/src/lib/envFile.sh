# Loads the script environment file or dies if it does not exist
loadEnvFile(){
  ENV_FILE=${ENV_FILE:-"${PROJECT_ROOT_DIR}/.env"}

  if [ ! -f ${ENV_FILE} ]; then
    if [ -f "${ENV_FILE}.tpl" ] && confirmDefaultYes "Looks like you're missing the ${ENV_FILE} file. Would you like to create it using the .env.tpl file?"; then
      cp "${ENV_FILE}.tpl" "${ENV_FILE}"
    else
      echo "Missing ${ENV_FILE} file! Please copy .env.tpl, rename it to .env and add the required values before continuing!";
      exit 1;
    fi
  fi

  source ${ENV_FILE}

  if ! [[ ${PROJECT_NAME} ]]; then
    echo "The PROJECT_NAME variable is not set in the .env file! Please set one before continuing...";
    local NAME=$(askForProjectName)
    echo "PROJECT_NAME=${NAME}" >> ${ENV_FILE}
  elif [[ ${PROJECT_NAME} = 'replace-me' ]]; then
    echo "Please replace the default project name in the .env file with your project name..."
    local NAME=$(askForProjectName)
    _sed "${ENV_FILE}" -i "s/PROJECT_NAME=replace-me/PROJECT_NAME=${NAME}/"
  fi
}

askForProjectName() {
  local NAME
  while true; do
    read -p 'project name: ' NAME
    if [[ ! ${NAME} =~ ^[a-zA-Z0-9-]+$ ]]; then
      echo "The project name can only contain alphanumeric characters and dashes!" > /dev/tty;
    else
      break
    fi
  done
  echo ${NAME}
}
