import sys
import re
import getopt

TEMPLATE_FILE_PATHS = [ './site/view/partial/FooterPartialTemplate.html' ]
BUILD_FILE_PATHS =    [ './site/view/partial/FooterPartial.html' ]

def githubSideRendering(argv):
    # constants
    ejs_pattern = "(<%=\s([a-zA-Z\-\_]+)\s%>)"
    if len(TEMPLATE_FILE_PATHS) != len(BUILD_FILE_PATHS):
        raise Exception("Invalid paths")

    # terminal argument handling
    arg_help = "{0} -h <help> <hash> <commit-msg> <commit-date>".format(argv[0])
    if (len(argv) == 1):
        sys.exit("No arguments passed to script")
    commit_hash = ""
    commit_msg = ""
    commit_date = ""

    try:
        opts, args = getopt.getopt(argv[1:], "h", ["help", "hash=", "commit-msg=", "commit-date="])
        if (len(args) != 0):
            sys.exit(f"Unknown arguments passed {args}")
    except getopt.GetoptError as err:
        sys.exit(f"Unknown command try: --help")
    for opt, arg in opts:
        if opt in ("-h", "--help"):
            print(arg_help)
            return
        elif opt in ("--hash"):
            commit_hash = arg
        elif opt in ("--commit-msg"):
            commit_msg = arg
        elif opt in ("--commit-date"):
            commit_date = arg

    # function body
    for i in range(len(TEMPLATE_FILE_PATHS)):
        TEMPLATE_FILE_PATH = TEMPLATE_FILE_PATHS[i]
        BUILD_FILE_PATH = BUILD_FILE_PATHS[i]
        template_file_content = ""
        with open(TEMPLATE_FILE_PATH) as f:
            template_file_content = f.read()

        print("🧊🧊🧊")
        print("input string:\n", template_file_content)
        print("🧊🧊🧊\n")

        matches = re.findall(ejs_pattern, template_file_content)
        if (len(matches) == 0):
            print(f"No matches found in {TEMPLATE_FILE_PATH}")
        for x in matches:
            key_in_templating_syntax = x[0] # Example value: <%= latest-version %>
            key = x[1]                      # Example value: latest-version
            if (not key):
                raise SystemError("Match pattern does not have 2 groups: ", x)
            if (key == "commit-hash"):
                print(f"replacing: {key} with '{commit_hash}'")
                template_file_content = template_file_content.replace(key_in_templating_syntax, commit_hash)
            if (key == "latest-version"):
                pattern = "(v\d+\.\d+\.\d+)"
                r = re.search(pattern, commit_msg)
                version = "No version number"
                if not r:
                    print(f"latest-version value does not match expected pattern. Regex: '{pattern}', example 'v1.6.13'. Replacing with '{version}'.")
                else:
                    version = r.group(0)
                print(f"replacing: {key} with '{version}'")
                template_file_content = template_file_content.replace(key_in_templating_syntax, version)
            if (key == "commit-date"):
                print(f"replacing: {key} with '{commit_date}'")
                template_file_content = template_file_content.replace(key_in_templating_syntax, commit_date)
        print("🔥🔥🔥")
        print("output string:\n", template_file_content)
        print("🔥🔥🔥")
        with open(BUILD_FILE_PATH, 'w') as f:
            f.write(template_file_content)


githubSideRendering(sys.argv)
