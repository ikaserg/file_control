import requests
import urllib
import json
import numpy as np

get_diff_url = "http://studio-tadema.net/control_files.php?action=diff"
file_list = json.loads(urllib.request.urlopen(get_diff_url).read())
chunk_size = 20
for files_chunk in np.array_split(np.array(file_list), chunk_size):
    r = requests.post("http://studio-tadema.net/control_files.php?action=delete_injected", \
                    json={'delete':[{'rel_path':f['rel_path']} for f in files_chunk if f['status'] == 3]})
