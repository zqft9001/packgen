--Vars

mod_name="Giantweevil's Importer"
version=0.4
self.setName(mod_name..' '..version)

site="https://giantweevil.net/"

self.interactable = false

helptext = [[
[FF0000]Read this![FFFFFF]
[32a8a2][b]S [keyrune code][/b][FFFFFF] - makes a pack with default settings based on the code provided.
[32a8a2][b]S [scryfall/gatherer card url][/b][FFFFFF] - makes the exact printing provided in the url.
[32a8a2][b]S Pack [keyrune code][/b][FFFFFF] - generates a pack by keyrune, or random if one provided
[32a8a2][b]S Card [image url] [cardname][/b][FFFFFF] - makes a card of the given name, with the image as the face.
[32a8a2][b]S Card [cardname][/b][FFFFFF] - makes all printings of the given name.
[32a8a2][b]S Token [cardname][/b][FFFFFF] - returns all tokens related to the cardname. Returns fuzzy search on partial names.
[32a8a2][b]S Set [keyrune code][/b][FFFFFF] - returns all cards printed in the set, minus basic lands
[32a8a2][b]S Help Deck[/b][FFFFFF] - Prints deck import help
[32a8a2][b]S Help Custom[/b][FFFFFF] - Prints back/scale help]]

helpdeck = [[
[FF0000]Read this![FFFFFF]
[32a8a2][b]S Deck [deck url][/b][FFFFFF] - creates a deck based on the given url. Autotranslates tappedout links, other links need to be text format.
[32a8a2][b]S Deck [deck name][/b][FFFFFF] - spawns a preconstructed deck.
[32a8a2][b]S Search [deck name][/b][FFFFFF] - finds preconstructed and user added decks.
[32a8a2][b]S Upload [deck url] [deck name][/b][FFFFFF] - uploads a deck from the url with the given name. Follows same rules as deck import for formatting.
[32a8a2][b]S Upload [deck name][/b][FFFFFF] - uploads all currently highlighted cards as a deck with the given name. Preserves printings.
[32a8a2][b]S Delete [deck name][/b][FFFFFF] - deletes a user-added deck.]]

helpcustom = [[
[32a8a2][b]S Back [image url][/b][FFFFFF] - sets the per-player cardback to [image url]. use without a url to reset to default.
[32a8a2][b]S GLOBALBACK [image url][/b][FFFFFF] - sets the global cardback to [image url]. use without a url to reset to default.
[32a8a2][b]S Scale [number][/b][FFFFFF] - sets the per-player scale to [number]. use without a number to reset to default
[32a8a2][b]S GLOBALSCALE [number][/b][FFFFFF] - sets the global scale to [number]. use without a number to reset to default.]]


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
	if self.getDescription() == "test" then
		return "t/"
	else
		return "p/"
	end
end

function settesting()
	if self.getDescription() ~= "test" then
		self.setDescription("test")
		self.setColorTint("Black")
		self.addContextMenuItem("Post Test", function() posttest() end)
		self.addContextMenuItem("Put Test", function() puttest() end)
		self.addContextMenuItem("Get Text", function() gettest() end)
	else
		self.setDescription("")
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

function back(owner)
	if globalback ~= nil then
		return "&back="..globalback
	elseif backurl[owner] ~= nil then
		return "&back="..backurl[owner]
	else
		return ""
	end
end

--returns cardscale if set, empty string otherwise
pscale = {}
globalscale = nil

function cardscale(owner)
	if globalscale ~= nil then
		return setscl(globalscale)
	elseif pscale[owner] ~= nil then
		return setscl(pscale[owner])
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

--Gets single card information by name. Random printToAlling.
function getcard(args, position, rotation)
	Wait.time(function()printToAll("Spawning object(s), please wait.")end, 0.5)
	local url = site..tp()..'ttscard/?'..args
	log(url)
	WebRequest.get(url, function(a) spawncard(a, position, rotation) end)
end

--Spawns a card from JSON
function spawncard(webReturn, position, rotation)
	if (webReturn.text == "") or (webReturn.text == nil) then
		Wait.time(function()printToAll("unable to spawn object(s), no return from site")end, 0.5)
		return
	end
	for i in string.gmatch(webReturn.text, "([^@]+)") do
		spawnObjectJSON({json=i, position=position, rotation=rotation})
	end

end

--gets single token information by original card name.

function selftoken(table)
	printToAll(table.owner..' spawns token(s) from '..table.name)
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
	gettoken("name="..table.name..back(table.owner)..cardscale(table.owner)..setpos({x=tpos.x, y=tpos.y, z=tpos.z})..setrot({x=trot.x, y=trot.y, z=0}))
end

function gettoken(args)
	Wait.time(function()printToAll("Spawning object(s), please wait.")end, 0.5)
	local url = site..tp()..'ttstoken/?'..args
	log(url)
	WebRequest.get(url, function(a) spawncard(a) end)
end

--Gets a pack by URL.
function getpack(url)
	Wait.time(function()printToAll("Spawning object(s), please wait.")end, 0.5)
	log(url)
	WebRequest.get(url, function(a) spawnpack(a.text) end)
end

--Spawns a pack from card JSONs, @ separated
function spawnpack(text)
	if (text == "") or (text == nil) then
		Wait.time(function()printToAll("unable to spawn object(s), no return from site")end, 0.5)
		return
	end
	for i in string.gmatch(text, "([^@]+)") do
		spawnObjectJSON({json=i})
	end
end

--Gets a deck by URL
function getdeck(url)
	Wait.time(function()printToAll("Spawning deck, please wait.")end, 0.5)
	log(url)
	WebRequest.get(url, function(a) spawndeck(a.text) end)
end

function spawndeck(text)
	if (text == "") or (text == nil) then
		Wait.time(function()printToAll("unable to spawn object(s), no return from site")end, 0.5)
		return
	end
	if string.match(text, "(Invalid Format)") then
		printToAll("Deck in invalid format.")
		return
	end
	for i in string.gmatch(text, "([^@]+)") do
		spawnObjectJSON({json=i})
	end
end

--Decksite to text file
function decktranslate(a)

	a = a:gsub('#.*', '')

	if a:match('tappedout.net') and not a:match('?fmt=txt') then
		a = a:gsub('?.*', '')
		a = a:gsub('.cb=%d+','')..'?fmt=txt'
	end
	if a:match('mtgdecks.net') and not a:match('/txt') then
		a = a:gsub('.cb=%d+','')..'/txt'
	end
	if a:match('mtggoldfish.com') then
		if not a:match('/deck/download/') then
			a = a:gsub('/deck/','/deck/download/')
		end

	end

	if a:match('deckstats.net') and not a:match('?export_txt=1') then
		a = a..'?export_txt=1'
	end

	if a:match('scryfall.com') and not a:match('/export/text') then
		a = a:gsub('.*/decks/','https://api.scryfall.com/decks/').."/export/text"
	end

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
	if imageurl:match('.jpg') or imageurl:match('.png') or imageurl:match('.webm') or imageurl:match('.mp4') or imageurl:match('.m4v') or imageurl:match('.mov') or imageurl:match('.rawt') or imageurl:match('.unity3d') then
		return imageurl
	else
		return nil
	end
end



--Tabletop Functions

function onLoad()
	printToAll(helptext)
	self.setDescription("")
	self.setColorTint({226/255, 177/255, 89/255})
end

function onChat(msg,player)

	--set owner

	local owner =  player.steam_name

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

	parseMessage(msg, position, rotation, owner)

end

function parseMessage(msg, position, rotation, owner)

	if msg:match('[Ss] (.*)') then

		local request=msg:match('[Ss] (.*)') or false

		--matches URls
		local url=request:match('(http%S+)')

		--matches the section after the card verb
		local card=request:match('[Cc]ard (.*)')

		--matches the section after the set verb
		local set=request:match('[Ss]et (.*)')

		--matches the secion after the token verb
		local token=request:match('[Tt]oken (.*)')

		--matches section after pack verb
		local pack=string.match(request, "^[Pp]ack (.*)")

		--matches section after deck verb
		local deck=string.match(request, "^[Dd]eck (.*)")

		--matches section after upload verb
		local upload=string.match(request, "^[Uu]pload (.*)")

		--matches section after delete verb
		local delete=string.match(request, "^[Dd]elete (.*)")

		--matches section after search verb
		local search=string.match(request, "^[Ss]earch (.*)")

		--matches number after scale verbs
		local scale=string.match(request, "[Ss][Cc][Aa][Ll][Ee] (.*)")

		--Bake args

		local exargs = back(owner)..setpos(position)..setrot(rotation)..cardscale(owner)..note(owner).."&GUID="..self.guid

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
				"s deck https://tappedout.net/mtg-decks/tts-importer-test/",
				"s deck mirror mastery",
				"s scale 2",
				"s jmp",
				"s scale",
				"s back https://i.imgur.com/hg32UEH.mp4",
				"s mh2",
				"s back",
				"s pack",
				"s card giant growth",
				"s card text/html"
			}

			local testposition = position

			for _,tmsg in ipairs(teststrings) do
				Wait.time(function()printToAll(tmsg)end, 0.5)
				parseMessage(tmsg, testposition, rotation, owner)
				testposition.x = testposition.x+5
			end

			--delete deck from importer site

		elseif string.match(request, "^[Dd]elete") and delete then

			deletebyname(delete)

			--search for deck on importer site

		elseif string.match(request, "^[Ss]earch") and search then

			searchbyname(search)

			--Upload deck to importer site

		elseif string.match(request, "^[Uu]pload") and upload and url then

			local upload = string.match(upload, "http%S+ (.*)")
			uploadbyurl(url, upload)

		elseif string.match(request, "^[Uu]pload") and upload then

			uploadbyuuid(upload, player)

			--Spawn deck by URL or name

		elseif string.match(request, "^[Dd]eck") and url then

			getdeck(site..tp()..'getdeck/?url='..decktranslate(url)..exargs)

		elseif string.match(request, "^[Dd]eck") and deck then

			getpack(site..tp().."precon/?search="..deck..exargs)

			--Token cards (and cards in token db)

		elseif string.match(request, "^[Tt]oken") and token then
			if not url then
				gettoken("name="..token..exargs)
			elseif string.match(token, "http%S+ (.*)") then
				gettoken("name="..token:match("http%S+ (.*)").."&face="..url..exargs)
			end

			--Card commands

		elseif url and string.match(url, "scryfall.com/card/") then
			getcard("set="..url:match("scryfall.com/card/([A-Za-z0-9]+)/*").."&cardnumber="..url:match("scryfall.com/card/[A-Za-z0-9]+/([A-Za-z0-9]+)/*")..exargs)

		elseif url and string.match(url, "gatherer.wizards.com/Pages/Card") then
			getcard("multiverseid="..url:match("multiverseid=(.[0-9]+)")..exargs)

		elseif string.match(request, "^[Cc]ard") and card then
			if not url then
				getcard("allprints=yes&name="..card..exargs)
			elseif card:match("http%S+ (.*)") then
				getcard("name="..card:match("http%S+ (.*)").."&face="..url..exargs)
			elseif url then
				getcard("name=island&face="..url..exargs)
			end

			--Spawn entire set

		elseif string.match(request, "^[Ss]et") and set then
			getcard("allprints=yes&set="..set..exargs)

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

			pscale[owner] = {x=scale, y=scale, z=scale}
			local setscalestr = "Set "..owner.."'s scale to "..scale
			Wait.time(function()printToAll(setscalestr)end, 0.5)

		elseif string.match(request, "^[Ss]cale") then

			pscale[owner] = nil
			Wait.time(function()printToAll("Cleared player scale")end, 0.5)

		elseif string.match(request, "^[Bb]ack") and url then
			backurl[owner] = backcheck(url)
			if backurl[owner] == nil then
				Wait.time(function()printToAll("Invalid back image provided.")end, 0.5)
			else
				local setbackstr = "Set "..owner.."'s back to "..backurl[owner]
				Wait.time(function()printToAll(setbackstr)end, 0.5)
			end

		elseif string.match(request, "^[Bb]ack") then
			backurl[owner] = nil
			Wait.time(function()printToAll("Cleared back url")end, 0.5)

			--Pack commands

		elseif string.match(request, "^[Pp]ack") and pack then

			getpack(site..tp().."?JSON=yes&set="..pack..exargs)

		elseif string.match(request, "^[Pp]ack") then

			getpack(site..tp().."?JSON=yes"..exargs)

		elseif string.match(request, "^[Jj][Mm][Pp]") then

			getpack(site..tp().."/precon/?JMP=yes"..exargs)

		elseif request then

			getpack(site..tp().."?JSON=yes&set="..request..exargs)

		end

		--Backup help matching

	elseif msg:match('?') or msg:match('help') then

		Wait.time(function()printToAll(helptext)end, 0.5)

	end

end

--EOF
