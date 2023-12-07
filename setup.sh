#!/bin/bash

# Check if the current directory is not the target one
  if [[ $PWD != *"/wp-content/plugins/artkko-submissions" ]]; then
    # Change to the target directory
    cd wp-content/plugins/artkko-submissions
  fi
  # Run composer install
  composer install