#!/bin/sh

# https://gist.github.com/gingerbeardman/a7737e4c89fccab8605f8538ddaeec0d

URL="https://file.io"
DEFAULT_EXPIRE="14d" # Default to 14 days

if [ $# -eq 0 ]; then
    echo "Usage: file.io FILE [DURATION]\n"
    echo "Example: file.io path/to/my/file 1w\n"
    exit 1
fi

FILE=$1
EXPIRE=${2:-$DEFAULT_EXPIRE}

if [ ! -f "$FILE" ]; then
    echo "File ${FILE} not found"
    exit 1
fi

RESPONSE=$(curl -# -F "file=@${FILE}" "${URL}/?expires=${EXPIRE}")

RETURN=$(echo "$RESPONSE" | php -r 'echo json_decode(fgets(STDIN))->success;')

if [ "1" != "$RETURN" ]; then
    echo "An error occured!\nResponse: ${RESPONSE}"
    exit 1
fi

KEY=$(echo "$RESPONSE" | php -r 'echo json_decode(fgets(STDIN))->key;')
EXPIRY=$(echo "${RESPONSE}" | php -r 'echo json_decode(fgets(STDIN))->link;')

echo "${URL}/${KEY}" | pbcopy # to clipboard
echo "${URL}/${KEY}"  # to terminal
