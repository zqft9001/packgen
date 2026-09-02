--Vars

mod_name = "Giantweevil's Importer"
version = 0.5
self.setName(mod_name..' '..version)

site = self.getDescription()
testing = false

self.interactable = false

helptext = [[
[dc322f][b]S Deck [deck url][/b][FFFFFF] - spawns a deck based on the given url. Autotranslates some links, other links need to be text format.
[dc322f][b]S JSON [deck url][/b][FFFFFF] - spawns a deck based on the given url, preserves printings. Autotranslates Scryfall links, other links are WIP.
[dc322f][b]S Decklist[/b][FFFFFF] - spawns a deck based on your color's notebook page. Accepts most text formats.

[6c71c4][b]S [JMP, J22, J25, or TLE][/b][FFFFFF] - spawns a jumpstart deck (2 packs) based on the code provided.
[6c71c4][b]S Card [cardname][/b][FFFFFF] - spawns all printings of the given name. Fuzzy search on partial matches.
[6c71c4][b]S Token [cardname][/b][FFFFFF] - spawns all tokens related to the name. Fuzzy search on partial matches.

[d33682][b]S Help Deck[/b][FFFFFF] - Prints deck import help
[d33682][b]S Help Custom[/b][FFFFFF] - Prints back/scale/specific printing help]]

helpdeck = [[
[dc322f][b]S Deck [deck url][/b][FFFFFF] - spawns a deck based on the given url. Autotranslates some links, other links need to be text format.
[dc322f][b]S Deck [deck name][/b][FFFFFF] - spawns a preconstructed or user added deck. Randomizes on multiple results.

[dc322f][b]S Decklist[/b][FFFFFF] - spawns a deck based on your color's notebook page. Accepts most formats.

[dc322f][b]S Search [deck name][/b][FFFFFF] - searches preconstructed and user added decks.
[dc322f][b]S Upload [deck url] [deck name][/b][FFFFFF] - uploads a deck from the url with the given name. Follows same rules as deck import for formatting.
[dc322f][b]S Upload [deck name][/b][FFFFFF] - uploads all currently highlighted cards as a deck with the given name. Preserves printings.
[dc322f][b]S Delete [deck name][/b][FFFFFF] - deletes a user-added deck.]]

helpcustom = [[
[d33682][b]S Back [image url][/b][FFFFFF] - sets the per-player cardback to [image url]. use without a url to reset to default.
[d33682][b]S GLOBALBACK [image url][/b][FFFFFF] - sets the global cardback to [image url]. use without a url to reset to default.

[d33682][b]S Scale [number][/b][FFFFFF] - sets the per-player scale to [number]. use without a number to reset to default
[d33682][b]S GLOBALSCALE [number][/b][FFFFFF] - sets the global scale to [number]. use without a number to reset to default.

[d33682][b]S [scryfall card url][/b][FFFFFF] - spawns the exact printing provided in the url.
[d33682][b]S Card [scryfall card url][/b][FFFFFF] - spwans the exact printing provided in the url.
[d33682][b]S Card [image url][/b][FFFFFF] - spawns an island with the image as the face.
[d33682][b]S Card [image url] [cardname][/b][FFFFFF] - spawns a card of the given name with the image as the face.

[d33682][b]S Set [keyrune code][/b][FFFFFF] - spawns all cards printed in the set, minus basic lands.]]


function onScriptingButtonDown(index, color)
	if index == 9 then
		if self.interactable == false then
			self.interactable = true
		else
			destroyObject(self)
		end
	end
end

--testing or prod site based on self Description
function tp()
	if testing == true then
		return "t/"
	else
		return "p/"
	end
end

function settesting()
	if testing == false then
		testing = true
		self.setColorTint("Black")
		self.addContextMenuItem("Post Test", function() posttest() end)
		self.addContextMenuItem("Put Test", function() puttest() end)
		self.addContextMenuItem("Get Text", function() gettest() end)
	else
		testing = false
		self.setColorTint({226/255, 177/255, 89/255})
		self.clearContextMenu()
		self.addContextMenuItem("Toggle Testing", function() settesting() end)
	end
end

self.addContextMenuItem("Toggle Testing", function() settesting() end)

function posttest()
	local url = site..'/t/request/'
	local data = { color = "Black", id = "12345", hi = "yes"}
	WebRequest.post(url, data, function(a) printToAll("POST sent") printToAll(a.text) end)
end

function puttest()
	local url = site..'/t/request/'
	local putstring = JSON.encode("url")
	WebRequest.put(url, putstring , function(a) printToAll("PUT sent") printToAll(a.text) end)
end

function gettest()
	local url = site..'/t/request/'
	WebRequest.get(url..'?get=yes', function(a) printToAll("GET sent") printToAll(a.text) end)
end


--returns cardback if set, empty string otherwise
backurl = {}
globalback = nil

--back by steam name
function back(sn)
	if globalback ~= nil then
		return "&back="..globalback
	elseif backurl[sn] ~= nil then
		return "&back="..backurl[sn]
	else
		return ""
	end
end

--returns cardscale if set, empty string otherwise
pscale = {}
globalscale = nil

--scale by steam name
function cardscale(sn)
	if globalscale ~= nil then
		return setscl(globalscale)
	elseif pscale[sn] ~= nil then
		return setscl(pscale[sn])
	else
		return ""
	end
end

function note(note)
	if note == "" then
		return ""
	else
		return "&note="..note
	end
end

--Functions

function setpos(pos)
	if pos then
		return '&pos='..pos.x..','..pos.y..','..pos.z
	end
	return ""
end

function setrot(rot)
	if rot then
		return '&rot='..rot.x..','..rot.y..','..rot.z
	end
	return ""
end

function setscl(scl)
	if scl then
		return '&scl='..scl.x..','..scl.y..','..scl.z
	end
	return ""
end

--Sends get request to website for object(s)
function getcard(url, objecttype, player)
	objecttype = objecttype or "object(s)"

	log(url)
	WebRequest.get(url, function(a) spawncard(a.text, objecttype, player) end)
end

--Spawns from @ separated JSON
function spawncard(text, objecttype, player)
	
	--if objecttype is unspecified, be vague
	objecttype = objecttype or "object(s)"

	if (text == "") or (text == nil) then
		Wait.time(function()printToAll("unable to spawn "..objecttype..", no return from site")end, 0.5)
		return
	end
	
	--if it's not probably JSON, return whatever text error the website gave
	if not string.match(text, "{") then
		Wait.time(function()printToAll(text)end, 0.5)
		return
	end
	
	--spawn @ separated JSON
	for i in string.gmatch(text, "([^@]+)") do
		spawnObjectJSON({json=i})
	end
	
	printToAll("["..Color.fromString(player.color):toHex(false).."]"..player.steam_name.."[FFFFFF] spawned "..objecttype)

end

--gets related tokens by original card name

function selftoken(table)
	local tpos = table.ref.getPosition()
	local trot = table.ref.getRotation()
	if trot.y >= 55 and trot.y < 145 then
		tpos.x = tpos.x - 3.18
	elseif trot.y >= 145 and trot.y < 235 then
		tpos.z = tpos.z + 3.18
	elseif trot.y >= 235 and trot.y < 325 then
		tpos.x = tpos.x + 3.18
	elseif trot.y >= 325 or trot.y < 55 then
		tpos.z= tpos.z - 3.18
	end
	parseMessage("s token "..table.name, {x=tpos.x, y=tpos.y, z=tpos.z}, {x=trot.x, y=trot.y, z=0}, Player[table.owner])
end



--Decksite to text file
function decktranslate(a)

	--website handles most stuff, #'s break get requests

	a = a:gsub('#.*', '')

	return a

end

--Deck uploadbyuuid

function uploadbyuuid(request, player)
	local gm = {}

	for _,j in ipairs(Player[player.color].getSelectedObjects()) do
		if j.name == "Deck" then
			for _,i in ipairs(j.getObjects()) do
				table.insert(gm, i.gm_notes)
			end
		elseif j.name == "Card" then
			table.insert(gm, j.getGMNotes())
		end
	end

	local deck = JSON.encode({deckname = request, cards = gm})

	local url = site..tp().."precon/json/"

	log(deck)

	WebRequest.put(url, deck, function(a) preconinfo(a.text) end)

end

--deck upload by URL
function uploadbyurl(url, name)
	local url = site..tp().."precon/json/?name="..name.."&url="..decktranslate(url)
	log(url)
	WebRequest.get(url, function(a) preconinfo(a.text) end)
end

--Deck delete by name

function deletebyname(name)
	local url = site..tp().."precon/json/?delete=yes&name="..name
	log(url)
	WebRequest.get(url, function(a) preconinfo(a.text) end)
end

--deck search by name

function searchbyname(name)
	local url = site..tp().."precon/json/?search="..name
	log(url)
	WebRequest.get(url, function(a) preconinfo(a.text) end)
end

--returns info from deck upload/delete/search

function preconinfo(text)
	printToAll(text)
end

--checks if a back URL is valid, returns nil if it isn't

function backcheck(imageurl)
	local returnurl = imageurl
	imageurl = imageurl:lower()
	if imageurl:match('.jpg') or imageurl:match('.png') or imageurl:match('.webm') or imageurl:match('.webp') or imageurl:match('.mp4') or imageurl:match('.m4v') or imageurl:match('.mov') or imageurl:match('.rawt') or imageurl:match('.unity3d') then
		return returnurl
	else
		return nil
	end
end



--Tabletop Functions

function onLoad()
	printToAll(helptext)
	self.setColorTint({226/255, 177/255, 89/255})
end

function onChat(msg,player)

	--pointer position is grabbed here to prevent decks spawning in multiple positions

	local position = player.getPointerPosition()

	if position == nil then
		position = {x=0, y=3, z=0}
	end
	position.y = position.y + 1

	--Rotation defaults to player view rotation and facedown.

	local py=player.getPointerRotation()
	if py==nil then
		py = 0
	end
	local rotation = {x=0, y=py, z=180}

	parseMessage(msg, position, rotation, player)

end

function parseMessage(msg, position, rotation, player)

	if msg:match('[Ss] (.*)') then

		local request=msg:match('[Ss] (.*)') or false

		--matches URls
		local url=request:match('(http%S+)')

		--matches number after scale verbs (used by global and player-specific)
		local scale=string.match(request, "[Ss][Cc][Aa][Ll][Ee] (.*)")
		
		--Bake args

		local exargs = back(player.steam_name)..setpos(position)..setrot(rotation)..cardscale(player.steam_name)..note(player.steam_name).."&GUID="..self.guid

		--help commands

		if string.match(request, "^[Hh]elp$") then

			Wait.time(function()printToAll(helptext)end, 0.5)

		elseif string.match(request, "^[Hh]elp [Dd]eck") then

			Wait.time(function()printToAll(helpdeck)end, 0.5)

		elseif string.match(request, "^[Hh]elp [Cc]ustom") then

			Wait.time(function()printToAll(helpcustom)end, 0.5)

			--unit tests

		elseif string.match(request, "TEST") then

			Wait.time(function()printToAll("UNIT TEST")end, 0.5)

			local teststrings = {
				"s card teysa",
				"s token teysa",
				"s token spirit",
				"s deck https://scryfall.com/@giantweevil/decks/4c651538-39ff-48b2-af31-cabce54551b5",
				"s json https://scryfall.com/@giantweevil/decks/4c651538-39ff-48b2-af31-cabce54551b5",
				"s deck mirror mastery",
				"s scale 2",
				"s jmp",
				"s scale",
				"s back https://i.imgur.com/hg32UEH.mp4",
				"s card teysa, envoy of",
				"s card teysa, orzhov scion",
				"s back",
				"s card giant growth",
				"s card text/html"
			}

			local testposition = position

			for _,tmsg in ipairs(teststrings) do
				Wait.time(function()printToAll(tmsg)end, 0.5)
				parseMessage(tmsg, testposition, rotation, player)
				testposition.x = testposition.x+5
			end

			
		--delete deck from importer site

		elseif string.match(request, "^[Dd]elete") then
		
			--matches section after delete verb
			local delete=string.match(request, "^[Dd]elete (.*)")

			deletebyname(delete)

		
		--search for deck on importer site

		elseif string.match(request, "^[Ss]earch") then
		
			--matches section after search verb
			local search=string.match(request, "^[Ss]earch (.*)")

			searchbyname(search)

		
		--Upload deck to importer site

		elseif string.match(request, "^[Uu]pload") and url then

			--matches section after upload verb and url
			local upload = string.match(request, "^[Uu]pload http%S+ (.*)")
			uploadbyurl(url, upload)

		elseif string.match(request, "^[Uu]pload") then
			
			--matches section after upload verb
			local upload=string.match(request, "^[Uu]pload (.*)")

			uploadbyuuid(upload, player)
			
		
		--spawn deck by list

		elseif string.match(request, "^[Dd]ecklist") then
			
			local decklist = ""

			for _, notebook in pairs(Notes.getNotebookTabs()) do
    				if notebook.title == player.color then
					decklist = notebook.body
    				end
  			end

			WebRequest.put(site..tp()..'getdeck/', JSON.encode({decklist = decklist, exargs = exargs}), function(a) spawncard(a.text, "deck from "..player.color.." notebook page", player) end)

			--Spawn deck by URL or name

		elseif string.match(request, "^[Dd]eck") and url then

			getcard(site..tp()..'getdeck/?url='..decktranslate(url)..exargs, "deck with random prints", player)

		elseif string.match(request, "^[Dd]eck") then
			
			--matches section after deck verb
			local deck=string.match(request, "^[Dd]eck (.*)")

			getcard(site..tp().."precon/?search="..deck..exargs, deck.." from precons and uploaded decks", player)

		elseif string.match(request, "^[Jj][Ss][Oo][Nn]") and url then
			
			getcard(site..tp()..'getdeck/?JSON=yes&url='..url..exargs, "deck with set prints", player)
			
			--Token cards (and cards in token db)

		elseif string.match(request, "^[Tt]oken") then
			
			--matches the section after the token verb
			local token=request:match('[Tt]oken (.*)')
			
			if not url then
				getcard(site..tp().."ttstoken/?name="..token..exargs, token.." token(s)", player)
			elseif string.match(token, "http%S+ (.*)") then
				getcard(site..tp().."ttstoken/?name="..token:match("http%S+ (.*)").."&face="..url..exargs, token.." token(s) with custom face", player)
			end

		
		--Card commands

		elseif url and string.match(url, "scryfall.com/card/") then
			getcard(site..tp().."ttscard/?set="..url:match("scryfall.com/card/([A-Za-z0-9]+)/*").."&cardnumber="..url:match("scryfall.com/card/[A-Za-z0-9]+/([A-Za-z0-9]+)/*")..exargs, player)

		elseif string.match(request, "^[Cc]ard") then
			
			--matches the section after the card verb
			local card=request:match('[Cc]ard (.*)')

			if not url then
				getcard(site..tp().."ttscard/?allprints=yes&name="..card..exargs, card, player)
			elseif card:match("http%S+ (.*)") then
				getcard(site..tp().."ttscard/?name="..card:match("http%S+ (.*)").."&face="..url..exargs, card.." with custom face", player)
			elseif url then
				getcard(site..tp().."ttscard/?name=island&face="..url..exargs, "island with custom face", player)
			end

		
		--Spawn entire set

		elseif string.match(request, "^[Ss]et") then
		
			--matches the section after the set verb
			local set=request:match('[Ss]et (.*)')
			
			if set then
				getcard(site..tp().."ttscard/?allprints=yes&set="..set..exargs, set, player)
			end

		
		--Custom spawn settings

		elseif string.match(request, "^GLOBALBACK") and url then

			globalback = backcheck(url)
			if globalback == nil then
				Wait.time(function()printToAll("Invalid back image provided.")end, 0.5)
			else
				local setbackstr = "Set global back to "..globalback
				Wait.time(function()printToAll(setbackstr)end, 0.5)
			end

		elseif string.match(request, "^GLOBALBACK") then

			globalback = nil
			Wait.time(function()printToAll("Cleared global back")end, 0.5)

		elseif string.match(request, "^GLOBALSCALE") and scale then

			globalscale = {x=scale, y=scale, z=scale}
			Wait.time(function()printToAll("Set global scale to "..scale)end, 0.5)

		elseif string.match(request, "^GLOBALSCALE") then

			globalscale = nil
			Wait.time(function()printToAll("Cleared global scale")end, 0.5)

		elseif string.match(request, "^[Ss]cale") and scale then

			pscale[player.steam_name] = {x=scale, y=scale, z=scale}
			local setscalestr = "Set "..player.steam_name.."'s scale to "..scale
			Wait.time(function()printToAll(setscalestr)end, 0.5)

		elseif string.match(request, "^[Ss]cale") then

			pscale[player.steam_name] = nil
			Wait.time(function()printToAll("Cleared player scale")end, 0.5)

		elseif string.match(request, "^[Bb]ack") and url then
			backurl[player.steam_name] = backcheck(url)
			if backurl[player.steam_name] == nil then
				Wait.time(function()printToAll("Invalid back image provided.")end, 0.5)
			else
				local setbackstr = "Set "..player.steam_name.."'s back to "..backurl[player.steam_name]
				Wait.time(function()printToAll(setbackstr)end, 0.5)
			end

		elseif string.match(request, "^[Bb]ack") then
			backurl[player.steam_name] = nil
			Wait.time(function()printToAll("Cleared back url")end, 0.5)

		
		--Pack commands

		elseif string.match(request, "^[Pp]ack") then
		
			--matches section after pack verb
			local pack=string.match(request, "^[Pp]ack (.*)")

			getcard(site..tp().."?JSON=yes&set="..pack..exargs, "pack", player)

		--original jumpstart
		
		elseif string.match(request, "^[Jj][Mm][Pp]") then

			getcard(site..tp().."/precon/?JMP=yes"..exargs, "JMP jumpstart (2 packs)", player)

		--avatar jumpstart
		
		elseif string.match(request, "^[Tt][Ll][Ee]") then

			getcard(site..tp().."/precon/?TLE=yes"..exargs, "TLE jumpstart (2 packs)", player)
		
		--jumpstart 2022
		
		elseif string.match(request, "^[Jj]22") then

			getcard(site..tp().."/precon/?J22=yes"..exargs, "J22 jumpstart (2 packs)", player)

		
		--foundations jumpstart
		
		elseif string.match(request, "^[Jj]25") then

			getcard(site..tp().."/precon/?J25=yes"..exargs, "J25 jumpstart (2 packs)", player)

		elseif request then

			getcard(site..tp().."?JSON=yes&set="..request..exargs, "pack", player)

		end

		--Backup help matching

	elseif msg:match('?') or msg:match('help') then

		Wait.time(function()printToAll(helptext)end, 0.5)

	end

end

--EOF
